/*
 * Simple wrapper that does chroot, seccomp,
 * and sets resource limits, before dropping privileges
 */

#define _XOPEN_SOURCE 1

#include <linux/seccomp.h>
#include <linux/filter.h>
#include <sys/resource.h>
#include <linux/audit.h>
#include <sys/ptrace.h>
#include <sys/socket.h>
#include <sys/prctl.h>
#include <sys/types.h>
#include <sys/time.h>
#include <unistd.h>
#include <string.h>
#include <stdlib.h>
#include <stddef.h>
#include <limits.h>
#include <errno.h>
#include <stdio.h>

extern char **environ;

#define SECURE_WRAP_MAX_ID 5242880LU
#define SECURE_WRAP_BASE_ID (1024LU * 1024LU)

#define check(command) if ((command) != 0) { perror(#command); return(1); }

#define error_eperm 1

int enable_seccomp(void) {
	struct sock_fprog filter;
	struct sock_filter rules[] = {
#include "filter.h"
	};

	filter.len = sizeof(rules) / sizeof(*rules);
	filter.filter = rules;

	check(prctl(PR_SET_NO_NEW_PRIVS, 1, 0, 0, 0))
	check(prctl(PR_SET_SECCOMP, SECCOMP_MODE_FILTER, &filter));

	return(0);
}

int main(int argc, char **argv) {
	const char *directory, *program;
	char *id_string;
	unsigned long id;
	struct rlimit limit;
	unsigned int tmp_fd;
	int unconstrained_user = 0;

	if (argc < 4) {
		fprintf(stderr, "usage: secure-wrap <id> <directory> <program> [<args>...]\n");

		return(2);
	}

	id_string = argv[1];
	id = strtoul(id_string, NULL, 0);
	argc--;
	argv++;

	if (id > SECURE_WRAP_MAX_ID) {
		fprintf(stderr, "error: id may not exceed %lu\n", SECURE_WRAP_MAX_ID);

		return(4);
	}

	directory = argv[1];
	argc--;
	argv++;

	program = argv[1];
	argc--;
	argv++;

	/*
	 * Determine if the user should be constrained
	 */
	if (getenv("SUID_FOSSIL_UNCONSTRAINED") != NULL) {
		unconstrained_user = 1;
	}

	/*
	 * chroot
	 */
	check(chdir(directory));
	check(chroot(directory));
	check(chdir("/"));

	/*
	 * Renice to avoid being able to use a lot of CPU
	 */
	setpriority(PRIO_PROCESS, 0, 19);

	/*
	 * Close all file descriptors
	 */
	limit.rlim_cur = 8192;
	limit.rlim_max = 8192;
	check(getrlimit(RLIMIT_NOFILE, &limit));
	for (tmp_fd = 3; tmp_fd < limit.rlim_max; tmp_fd++) {
		if (tmp_fd > INT_MAX) {
			return(3);
		}
		close(tmp_fd);
	}

	/*
	 * Set resource limits
	 */
	/**
	 ** Disallow many kinds of resources entirely
	 **/
	limit.rlim_cur = 0;
	limit.rlim_max = 0;
	check(setrlimit(RLIMIT_CORE, &limit));
	check(setrlimit(RLIMIT_LOCKS, &limit));
	check(setrlimit(RLIMIT_MEMLOCK, &limit));
	check(setrlimit(RLIMIT_MSGQUEUE, &limit));

	/**
	 ** Allow a reasonable number of file descriptors
	 **/
	limit.rlim_cur = 32;
	limit.rlim_max = 32;
	check(setrlimit(RLIMIT_NOFILE, &limit));

	/**
	 ** Allow a reasonable number of processes
	 **/
	limit.rlim_cur = 10;
	limit.rlim_max = 10;
	check(setrlimit(RLIMIT_NPROC, &limit));

	/**
	 ** Allow a reasonable amount of CPU time
	 **/
	limit.rlim_cur = 300;
	limit.rlim_max = 300;
	check(setrlimit(RLIMIT_CPU, &limit));

	/**
	 ** Allow a reasonable amount of RAM
	 **/

	/***
	 *** 512MiB of available memory (unless unconstrained user)
	 ***/
	if (unconstrained_user) {
		limit.rlim_cur = 1024 * 1024 * 1024 * 4LU;
		limit.rlim_max = 1024 * 1024 * 1024 * 4LU;
	} else {
		limit.rlim_cur = 1024 * 1024 * 512LU;
		limit.rlim_max = 1024 * 1024 * 512LU;
	}
	check(setrlimit(RLIMIT_DATA, &limit));
	check(setrlimit(RLIMIT_RSS, &limit));

	/***
	 *** 16MiB of stack space
	 ***/
	limit.rlim_cur = 1024 * 1024 * 16LU;
	limit.rlim_max = 1024 * 1024 * 16LU;
	check(setrlimit(RLIMIT_STACK, &limit));

	/***
	 *** 8GiB of Address Space
	 ***/
	limit.rlim_cur = 1024 * 1024 * 8192LU;
	limit.rlim_max = 1024 * 1024 * 8192LU;
	check(setrlimit(RLIMIT_AS, &limit));

	/*
	 * Drop privileges
	 */
	check(setgid(SECURE_WRAP_BASE_ID + id));
	check(setuid(SECURE_WRAP_BASE_ID + id));

	/*
	 * Install seccomp filter
	 */
	check(enable_seccomp());

	/*
	 * Execute program
	 */
	check(execve(program, argv, environ));

	/*
	 * Failed to execute program
	 */
	return(1);
}
