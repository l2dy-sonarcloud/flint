/*
 * Simple wrapper that does chroot, seccomp,
 * and sets resource limits, before dropping privileges
 */

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
	char *program_environment[6];
	char *secure_home, *secure_user;
	char *id_string;
	unsigned long id;
	struct rlimit limit;
	unsigned int tmp_fd;

	program_environment[0] = "HOME=/";
	program_environment[1] = "TMPDIR=/tmp";
	program_environment[2] = "PATH=/bin";
	program_environment[3] = "TZ=UTC";
	program_environment[4] = NULL;
	program_environment[5] = NULL;

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
	check(setrlimit(RLIMIT_FSIZE, &limit));

	/**
	 ** Allow a reasonable number of file descriptors
	 **/
	limit.rlim_cur = 16;
	limit.rlim_max = 16;
	check(setrlimit(RLIMIT_NOFILE, &limit));

	/**
	 ** Allow a reasonable number of processes
	 **/
	limit.rlim_cur = 5;
	limit.rlim_max = 5;
	check(setrlimit(RLIMIT_NPROC, &limit));

	/**
	 ** Allow a reasonable amount of CPU time
	 **/
	limit.rlim_cur = 90;
	limit.rlim_max = 90;
	check(setrlimit(RLIMIT_CPU, &limit));

	/**
	 ** Allow a reasonable amount of RAM
	 **/
	limit.rlim_cur = 1024 * 1024 * 64LU;
	limit.rlim_max = 1024 * 1024 * 64LU;
	check(setrlimit(RLIMIT_DATA, &limit));
	check(setrlimit(RLIMIT_RSS, &limit));
	check(setrlimit(RLIMIT_STACK, &limit));

	limit.rlim_cur = 1024 * 1024 * 1024LU;
	limit.rlim_max = 1024 * 1024 * 1024LU;
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
	 * Allow a user-specified HOME directory to be set
	 */
	secure_home = getenv("SECURE_WRAP_HOME");
	if (secure_home) {
		secure_home = strdup(secure_home - 5);

		if (secure_home) {
			program_environment[0] = secure_home;

			if (memcmp(program_environment[0], "HOME=", 5) != 0) {
				return(5);
			}
		}
	}

	/*
	 * Allow a user-specified USER variable to be set
	 */
	secure_user = getenv("SECURE_WRAP_USER");
	if (secure_user) {
		secure_user = strdup(secure_user - 5);

		if (secure_user) {
			program_environment[4] = secure_user;

			if (memcmp(program_environment[4], "USER=", 5) != 0) {
				return(6);
			}
		}
	}

	/*
	 * Execute program
	 */
	check(execve(program, argv, program_environment));

	/*
	 * Failed to execute program
	 */
	return(1);
}
