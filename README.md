<!--
SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
SPDX-License-Identifier: CC0-1.0
-->

# Jukebox

**Jukebox** is a Nextcloud app for streaming and organizing all your audio content in one place.  
It supports music files, podcasts (with gPodder sync), audiobooks, YouTube videos, and online radio.

## Features

- 🎵 Play local music files
- 🎙️ Sync and stream podcasts using [gPodder](https://gpodder.net/)
- 📚 Listen to audiobooks with resume support
- 📺 Embed and play YouTube videos
- 📻 Tune in to online radio streams

## Installation

Download the app from [Nextcloud's App Store](https://apps.nextcloud.com/apps/jukebox) through your
Nextcloud instance.

If you prefer to download manually, you can download the latest version from GitHub and install
directly:

1. Place this app in **nextcloud/apps/** or **nextcloud/custom_apps/**

2. Here is a quick installation script you can use as base. Modify the first variable lines to match
   your setup:

   ```bash
   pushd "/path/to/root/of/nextcloud/custom_apps"

   APPVER=$(curl -s https://api.github.com/repos/chenasraf/nextcloud-jukebox/releases/latest | grep tag_name | grep -Eo 'v[^"]+') && \
   curl -L https://github.com/chenasraf/nextcloud-jukebox/releases/download/${APPVER}/jukebox-${APPVER}.tar.gz -o jukebox.tar.gz && \
   tar xfv jukebox.tar.gz && \
   rm -rf jukebox.tar.gz
   ```

3. Then enable the app as you normally would from Nextcloud's Apps page.

## Contributing

I am developing this package on my free time, so any support, whether code, issues, or just stars is
very helpful to sustaining its life. If you are feeling incredibly generous and would like to donate
just a small amount to help sustain this project, I would be very very thankful!

<a href='https://ko-fi.com/casraf' target='_blank'>
  <img height='36' style='border:0px;height:36px;'
    src='https://cdn.ko-fi.com/cdn/kofi1.png?v=3'
    alt='Buy Me a Coffee at ko-fi.com' />
</a>

I welcome any issues or pull requests on GitHub. If you find a bug, or would like a new feature,
don't hesitate to open an appropriate issue and I will do my best to reply promptly.

## Development

### Automation

Most development processes are automated:

- **GitHub Actions** run tests, builds, and validations on each push or pull request.
- **Pre-commit formatting** is handled by [lint-staged](https://github.com/okonet/lint-staged),
  which automatically formats code before committing:

> 🛠️ The NPM package [husky](https://www.npmjs.com/package/husky) takes care of installing the
> pre-commit hook automatically after `pnpm install`.

---

### Manual Commands

While automation handles most workflows, the following commands are available for local development
and debugging:

#### Build the App

```bash
make
```

Installs dependencies and compiles frontend/backend assets.

#### Run Tests

```bash
make test
```

Runs unit and integration tests (if available).

#### Format & Lint

```bash
make format   # Auto-fix code style
make lint     # Check code quality
```

#### Generate OpenAPI Docs

```bash
make openapi
```

Output is saved to `build/openapi/openapi.json`.

#### Packaging for Release

```bash
make appstore    # Production build for Nextcloud app store
make source      # Full source package
make distclean   # Clean build artifacts and dependencies
```

#### Sign Releases

After uploading the archive to GitHub:

```bash
make sign
```

Downloads the `.tar.gz` release, verifies it, and prints a SHA-512 signature using your key at
`~/.nextcloud/certificates/jukebox.key`.
