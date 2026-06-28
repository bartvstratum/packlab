# PackLab
Back/bike packing list

## Run locally

Requires PHP 8+ with `pdo_sqlite`.

```
cp config.example.php config.php   # first time only
php -S localhost:8000
```

Then open http://localhost:8000/index.php

The database is created automatically on first run. To load a list from a
LighterPack-format CSV:

```
php csv.php import <userId> <file.csv> "List name"
```
