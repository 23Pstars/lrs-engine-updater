# LRS Engine Updater

Updater untuk website yang menggunakan `LRS Engine`.

## Usages
Upload script `lrs-engine.php` ke `public_html` atau web root directory lainnya.
Jika sudah di `public_html` field `path` isi dengan nama domain, `example-domain.com`.

## Credentials
Sesuaikan `$username` dan `$password` dengan akun Bitbucket masing-masing.

## Domain lists
Kami menggunakan format Bitbucket untuk host domain lists karena alasan privasi, namun format RAW domain lists adalah:
```markdown
# Domain that using LRS Engine
- https://domain1.com
- https://domain2.com
- ...
```
