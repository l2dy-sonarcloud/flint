CRYSTAL_PATH := /opt/appfs/rkeene.org/crystal/platform/latest
CRYSTAL      := $(CRYSTAL_PATH)/bin/crystal
SHARDS       := $(CRYSTAL_PATH)/bin/shards
DEBUG_OR_RELEASE := --debug

all: suid-fossil

lib: shard.lock
	rm -rf lib
	$(SHARDS) install

shard.lock: shard.yml

suid-fossil: suid-fossil.cr lib
	$(CRYSTAL) build $(DEBUG_OR_RELEASE) -o suid-fossil suid-fossil.cr

clean:
	rm -f suid-fossil

distclean: clean
	rm -rf lib

mrproper: distclean
	rm -f shard.lock
	$(MAKE) lib
	rm -rf lib

.PHONY: all clean distclean
.SUFFIXES:
