#! /opt/appfs/rkeene.org/crystal/platform/latest/bin/crystal

require "sqlite3"
require "ini"

# User-specified constants
## UID offset when mapping Flint user IDs to OS user IDs
UID_OFFSET = 1024 * 1024

## 
FLINT_ROOT = "/home/chiselapp/chisel"

# Import the C functions for setuid/setgid
lib C
	fun setuid(uid : Int32) : Int32
	fun setgid(gid : Int32) : Int32
end

# Usage information
def print_help
	print("Usage: suid-fossil <fossil-args...>\n")
end

# Convert a UserID or a Username to the other via the DB
def userid_from_db(userdb : String, username : String) : Int32
	userid = nil

	DB.open "sqlite3://#{userdb}" {|db|
		userid = db.query_one "SELECT id FROM users WHERE username = ? LIMIT 1", username, as: {Int32}
	}

	if userid.nil?
		raise "User Name could not be found"
	end

	userid
end

def username_from_db(userdb : String, userid : Int32) : String
	username = nil

	DB.open "sqlite3://#{userdb}" {|db|
		username = db.query_one "SELECT username FROM users WHERE id = ? LIMIT 1", userid, as: {String}
	}

	if username.nil?
		raise "User ID could not be found"
	end

	username
end

# Find the Flint DB given a path to the Flint root
def find_db(root : String) : String
	dbconfig_file = File.join(root, "config", "sqlite.cnf")

	dbconfig_text = File.read(dbconfig_file)

	dbconfig = INI.parse(dbconfig_text)

	dbfile = dbconfig["config"]["database"]
	dbfile = File.expand_path(dbfile, File.join(root, "db"))

	dbfile
end

# Find a Flint User ID from an OS File
def userid_from_file(file : String) : Int32
	info = File.info(file)

	Int32.new(info.owner - UID_OFFSET)
end

# Run Fossil, wrapped as a Flint UserName/UserID
def suid_fossil(username : String, userid : Int32, fossil_args : Array, fossil_command = "fossil")
	# Compute OS UID from Flint User ID
	uid = userid + UID_OFFSET

	# Create Fossil home directory
	home = "/tmp/suid-fossil/#{userid}"

	if !Dir.exists?(home)
		Dir.mkdir(home, 0o700)
		File.chown(home, uid, uid)
	end

	ENV["HOME"] = home

	# Set OS UID/GID
	## Opportunistic -- if it fails, we do not care
	C.setgid(uid)

	uidcheck = C.setuid(uid)
	if (uidcheck != 0)
		raise "Unable to switch to UID #{uid}"
	end

	# If possible, update environment with usernames
	ENV["USER"] = username
	ENV["USERNAME"] = username

	Process.exec(fossil_command, fossil_args)

	raise "Failed to run Fossil"
end

# -------------------------------
# MAIN
# -------------------------------
fossil_args = ARGV

flint_root = ENV.fetch("FLINT_ROOT", FLINT_ROOT)
userid     = ENV["FLINT_USERID"]?
username   = ENV["FLINT_USERNAME"]?

# Find DB if possible
userdb   = ENV["FLINT_USERDB"]?

if userdb.nil?
	if !flint_root.nil?
		userdb = find_db(flint_root)
	end
end

# Find User ID
## Check to see if this is a CGI call, if so
## we take the user ID from the filename
if userid.nil?
	if ENV.has_key?("GATEWAY_INTERFACE")
		userid = userid_from_file(ARGV[0])
	end
end

if userid.nil?
	if username.nil?
		raise "Unhandled -- must specify one of FLINT_USERNAME or FLINT_USERID"
	end

	if userdb.nil?
		raise "Unhandled -- must specify FLINT_USERDB or FLINT_ROOT"
	end

	userid = userid_from_db(userdb, username)
else
	userid = userid.to_i32()
end

# Find User Name
if username.nil?
	if userdb.nil?
		raise "Unhandled -- must specify FLINT_USERDB or FLINT_ROOT"
	end

	username = username_from_db(userdb, userid)
end

# Run Fossil
suid_fossil(username, userid, fossil_args)
