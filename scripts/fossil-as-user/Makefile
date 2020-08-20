all: suid-fossil secure-wrap

filter.h: generate-seccomp-filter filter.seccomp Makefile
	./generate-seccomp-filter filter.seccomp i386 "" x86_64 "" > filter.h.new
	mv filter.h.new filter.h

secure-wrap: secure-wrap.c filter.h
	gcc -o secure-wrap -W -Wall secure-wrap.c
	sudo chown root:root secure-wrap
	sudo chmod +s secure-wrap

clean:
	rm -f filter.h secure-wrap

distclean: clean

mrproper: distclean

.PHONY: all clean distclean
.SUFFIXES:
