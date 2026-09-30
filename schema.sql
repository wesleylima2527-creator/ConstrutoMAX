PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    nome          TEXT NOT NULL,
    email         TEXT NOT NULL UNIQUE,
    whatsapp      TEXT,
    password_hash TEXT NOT NULL,
    created_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS quotes (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER REFERENCES users(id) ON DELETE SET NULL,
    nome       TEXT NOT NULL,
    whatsapp   TEXT NOT NULL,
    mensagem   TEXT NOT NULL,
    status     TEXT NOT NULL DEFAULT 'novo',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_quotes_user ON quotes(user_id);

CREATE TABLE IF NOT EXISTS categories (
    slug TEXT PRIMARY KEY,
    nome TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS products (
    id          TEXT PRIMARY KEY,
    category    TEXT NOT NULL REFERENCES categories(slug),
    nome        TEXT NOT NULL,
    descricao   TEXT NOT NULL,
    unidade     TEXT NOT NULL,
    price_cents INTEGER NOT NULL CHECK (price_cents >= 0),
    imagem      TEXT,
    destaque    INTEGER NOT NULL DEFAULT 0,
    ativo       INTEGER NOT NULL DEFAULT 1
);
CREATE INDEX IF NOT EXISTS idx_products_category ON products(category);
