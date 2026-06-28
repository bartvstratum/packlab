PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  email         TEXT NOT NULL UNIQUE,
  name          TEXT,
  password_hash TEXT NOT NULL,
  created_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS lists (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  name        TEXT NOT NULL,
  share_token TEXT UNIQUE,
  position    INTEGER NOT NULL DEFAULT 0,
  created_at  TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS categories (
  id        INTEGER PRIMARY KEY AUTOINCREMENT,
  list_id   INTEGER NOT NULL REFERENCES lists(id) ON DELETE CASCADE,
  name      TEXT NOT NULL,
  color     TEXT,
  icon      TEXT,
  position  INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS items (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
  name        TEXT NOT NULL,
  description TEXT,
  weight      REAL NOT NULL DEFAULT 0,
  qty         INTEGER NOT NULL DEFAULT 1,
  worn        INTEGER NOT NULL DEFAULT 0,
  consumable  INTEGER NOT NULL DEFAULT 0,
  url         TEXT,
  position    INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_lists_user      ON lists(user_id);
CREATE INDEX IF NOT EXISTS idx_categories_list ON categories(list_id);
CREATE INDEX IF NOT EXISTS idx_items_category  ON items(category_id);
