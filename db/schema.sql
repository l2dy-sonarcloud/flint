CREATE TABLE repositories (
    id integer primary key autoincrement,
    user_id integer,
    name text,
    private integer,
    cloned integer,
    auto_update integer
);

CREATE TABLE sessions (
    user_id integer primary key,
    session_id text,
    session_date text
);

CREATE TABLE tokens (
    user_id integer primary key,
    token text,
    create_date text
);

CREATE TABLE users (
    id integer primary key autoincrement,
    first_name text,
    last_name text,
    email text,
    username text,
    password text,
    salt text
);
