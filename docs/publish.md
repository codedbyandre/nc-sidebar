<!--
  SPDX-FileCopyrightText: 2026 André Wiesehoff
  SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Publish a release

One path. A GitHub release from a `vX.Y.Z` tag builds the store archive, signs it, attaches it to that release, and uploads it to the Nextcloud app store. The workflow is `.github/workflows/release.yml`. It runs only after the release is published, and only from the workflow file on `main`.

The private key stays in `~/.nextcloud/certificates/sidebar.key` and in the Actions secret `APP_PRIVATE_KEY`. It is not a repository file.

The public certificate is not registered yet. The workflow expects it at [sidebar.crt](https://github.com/nextcloud/app-certificate-requests/blob/master/sidebar/sidebar.crt) once that request exists. Until then, `make appstore` still builds an unsigned tarball.

## Once

1. Request an app certificate for the id `sidebar`.
2. On GitHub, open **Settings → Secrets and variables → Actions** for this repository.
3. Create `APP_PRIVATE_KEY`. Paste the full PEM from `~/.nextcloud/certificates/sidebar.key`, including the `BEGIN` and `END` lines.
4. Create `APPSTORE_TOKEN`. On [apps.nextcloud.com](https://apps.nextcloud.com), open the account menu and copy the API token.
5. Push this workflow to `main` before publishing a release. GitHub reads the workflow from `main`, then checks out the tag.

## Each release

1. Put the same version in `appinfo/info.xml` and `package.json`.
2. Add a heading `## X.Y.Z - YYYY-MM-DD` at the top of `CHANGELOG.md`. The store reads that shape. `## [X.Y.Z]` does not match. Copy `CHANGELOG.md` over `CHANGELOG.en.md` so the two files stay identical.
3. Commit that on `main` and push `main`.
4. Tag that exact commit and push the tag:

```sh
git tag vX.Y.Z
git push origin vX.Y.Z
```

5. Open a GitHub release from that tag and publish it. Leave "Set as a pre-release" off. A draft does not start the workflow. A prerelease stops it.
6. Wait until the **Release** workflow is green.

The tag, `info.xml`, and `package.json` must all say the same version. `v0.2.0` ships version `0.2.0`. The workflow builds the frontend, then runs `make appstore SIGN=required`. That refuses to upload when `appinfo/signature.json` is missing.

`make appstore` copies an allowlist into `build/appstore/sidebar`: `appinfo`, `lib`, `templates`, `css`, `js`, `img`, `l10n`, `LICENSES`, `CHANGELOG.md`, and `CHANGELOG.en.md`. Source maps stay out. The top-level folder is `sidebar`.

## Local check

`make appstore` without `SIGN=required` builds an unsigned archive. A signed archive needs a Nextcloud tree and the key.

```sh
export NEXTCLOUD_ROOT=/path/to/nextcloud
make appstore SIGN=required
```

## Screenshots

`appinfo/info.xml` points at `docs/screenshots/files.png` and `docs/screenshots/admin.png` on `main`. The store does not read those files from the release archive. `docs/` stays out of the tarball. The gallery on apps.nextcloud.com loads each URL through `usercontent.apps.nextcloud.com`.

Handbook pictures that are not in the store gallery also live in that folder. `files-wide.png`, `drawer.png`, and `personal.png`.

The store gallery fetches each URL once and keeps the result. Since about 21 July 2026 that cache stores the file as mode `0600`, so the gallery can answer HTTP 200 with an empty body. The files in this repository are still the ones to keep. A new path only creates a new cache entry with the same problem.

## Certificate request

The signing key and the certificate request are local files, not repository files.

1. `~/.nextcloud/certificates/sidebar.key`
2. `~/.nextcloud/certificates/sidebar.csr`, subject `CN=sidebar`

Open a request in [nextcloud/app-certificate-requests](https://github.com/nextcloud/app-certificate-requests) with that `.csr`. After Nextcloud publishes `sidebar/sidebar.crt`, the release workflow downloads it. Do not commit the key.
