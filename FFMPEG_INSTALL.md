# FFMpeg Installation Guide

## Ubuntu/Debian
```bash
sudo apt update
sudo apt install ffmpeg
```

## CentOS/RHEL
```bash
sudo yum install epel-release
sudo yum install ffmpeg
```

## macOS
```bash
brew install ffmpeg
```

## Windows
Download from: https://ffmpeg.org/download.html

## Verify Installation
```bash
ffmpeg -version
```

## PHP FFMpeg Package
```bash
composer require php-ffmpeg/php-ffmpeg
```

## Configuration
Add to .env:
```
FFMPEG_BINARIES=/usr/bin/ffmpeg
FFPROBE_BINARIES=/usr/bin/ffprobe
```
