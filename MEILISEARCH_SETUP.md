# Meilisearch Setup Guide

Meilisearch is used for fast, typo-tolerant search across posts, users, and hashtags.

## 🚀 Installation

### Windows

1. **Download Meilisearch**
```bash
# Already included in project root: meilisearch.exe
```

2. **Start Meilisearch**
```bash
meilisearch.exe --master-key="masterKey123" --http-addr="127.0.0.1:7700"
```

### Linux/macOS

```bash
# Download
curl -L https://install.meilisearch.com | sh

# Start
./meilisearch --master-key="masterKey123" --http-addr="127.0.0.1:7700"
```

### Docker

```bash
docker run -d \
  --name meilisearch \
  -p 7700:7700 \
  -e MEILI_MASTER_KEY="masterKey123" \
  getmeili/meilisearch:latest
```

## ⚙️ Configuration

### Environment Variables

Add to `.env`:
```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=masterKey123
```

### Laravel Scout Config

Already configured in `config/scout.php`:
```php
'meilisearch' => [
    'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
    'key' => env('MEILISEARCH_KEY'),
],
```

## 📊 Indexed Models

### Post Model
- **Index**: `posts`
- **Searchable Fields**: content, user_name, hashtags
- **Filterable**: published_at, likes_count, user_id
- **Sortable**: published_at, likes_count

### User Model
- **Index**: `users`
- **Searchable Fields**: name, username, bio
- **Filterable**: is_verified, is_premium
- **Sortable**: followers_count, created_at

### Hashtag Model
- **Index**: `hashtags`
- **Searchable Fields**: name
- **Filterable**: posts_count
- **Sortable**: posts_count

## 🔧 Initial Setup

### 1. Import Existing Data
```bash
# Import all posts
php artisan scout:import "App\Models\Post"

# Import all users
php artisan scout:import "App\Models\User"

# Import all hashtags
php artisan scout:import "App\Models\Hashtag"
```

### 2. Verify Indexes
```bash
curl http://127.0.0.1:7700/indexes \
  -H "Authorization: Bearer masterKey123"
```

### 3. Check Index Stats
```bash
curl http://127.0.0.1:7700/indexes/posts/stats \
  -H "Authorization: Bearer masterKey123"
```

## 🔍 Search Examples

### Search Posts
```bash
curl -X POST http://127.0.0.1:7700/indexes/posts/search \
  -H "Authorization: Bearer masterKey123" \
  -H "Content-Type: application/json" \
  -d '{"q": "laravel"}'
```

### Search Users
```bash
curl -X POST http://127.0.0.1:7700/indexes/users/search \
  -H "Authorization: Bearer masterKey123" \
  -H "Content-Type: application/json" \
  -d '{"q": "john"}'
```

## 🛠️ Maintenance

### Flush Index
```bash
php artisan scout:flush "App\Models\Post"
```

### Re-import Data
```bash
php artisan scout:flush "App\Models\Post"
php artisan scout:import "App\Models\Post"
```

### Clear All Indexes
```bash
curl -X DELETE http://127.0.0.1:7700/indexes/posts \
  -H "Authorization: Bearer masterKey123"
```

## 📈 Performance Tips

1. **Use Filters**: Filter before searching for better performance
2. **Limit Results**: Use `limit` parameter to reduce response size
3. **Pagination**: Use `offset` and `limit` for pagination
4. **Typo Tolerance**: Meilisearch handles typos automatically

## 🔒 Security

### Production Settings

```env
MEILISEARCH_KEY=your_secure_random_key_here
```

Generate secure key:
```bash
openssl rand -base64 32
```

### Firewall Rules

Only allow localhost access:
```bash
# Linux
sudo ufw allow from 127.0.0.1 to any port 7700

# Windows Firewall
netsh advfirewall firewall add rule name="Meilisearch" dir=in action=allow protocol=TCP localport=7700
```

## 🐛 Troubleshooting

### Connection Refused
```bash
# Check if Meilisearch is running
curl http://127.0.0.1:7700/health

# Restart Meilisearch
# Windows: Close meilisearch.exe and restart
# Linux: killall meilisearch && ./meilisearch --master-key="masterKey123"
```

### Index Not Found
```bash
# Re-import data
php artisan scout:import "App\Models\Post"
```

### Slow Search
```bash
# Check index size
curl http://127.0.0.1:7700/indexes/posts/stats \
  -H "Authorization: Bearer masterKey123"

# Consider splitting large indexes
```

## 📚 Resources

- [Meilisearch Documentation](https://docs.meilisearch.com)
- [Laravel Scout Documentation](https://laravel.com/docs/scout)
- [Meilisearch API Reference](https://docs.meilisearch.com/reference/api)

---

**Note**: Meilisearch must be running for search functionality to work. Add it to your system startup or use a process manager like Supervisor.
