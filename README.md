# PackLab - gear list editor

Sunday vibe-coding project: a reimplementation of LighterPack with (hopefully) fewer bugs,
better mobile editing, and a few personal preferences baked in — most notably
**sane units only**.

![PackLab screenshot](images/packlab_example.png)

The CSV files are **two-way compatible** with LighterPack: you can import a LighterPack
export here and export a file that imports back into LighterPack. However, on import,
weights in oz/lb/kg are **converted to grams**.

Most of the code was written by Claude (Anthropic's Claude Code, model Claude Opus 4.8)

## Run locally

Requires PHP 8+ with `pdo_sqlite`. On Ubuntu/Debian:

```
sudo apt install php-cli php-sqlite3
```

Then:

```
cp config.example.php config.php   # first time only
php -S localhost:8000
```

Then open http://localhost:8000/index.php

The database is created automatically on first run.
