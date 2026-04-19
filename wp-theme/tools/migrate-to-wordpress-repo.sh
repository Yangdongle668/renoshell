#!/usr/bin/env bash
#
# Renobattery — migrate the wp-theme/ subtree out of `renoshell` into
# `github.com/Yangdongle668/wordpress` as the repo root, preserving the
# full commit history for files under wp-theme/.
#
# Prerequisites on your local machine:
#   - git (>= 2.20)
#   - bash
#   - GitHub auth for Yangdongle668/wordpress (HTTPS PAT in credential
#     manager, or SSH key + switch the URL below)
#
# Usage:
#   bash migrate-to-wordpress-repo.sh            # dry run (no push)
#   bash migrate-to-wordpress-repo.sh --push     # actually push
#
# Safe to rerun: recreates the work dir from scratch each time.
# Does NOT modify the source `renoshell` repo.

set -euo pipefail

# ─── Config ─────────────────────────────────────────────────────────
SOURCE_REPO="https://github.com/Yangdongle668/renoshell.git"
SOURCE_BRANCH="claude/wordpress-battery-theme-w90Cw"
SUBTREE_PREFIX="wp-theme"

TARGET_REPO="https://github.com/Yangdongle668/wordpress.git"
TARGET_BRANCH="main"

WORK_DIR="${WORK_DIR:-./renobattery-migrate-work}"
EXTRACT_BRANCH="renobattery-extracted"
# ────────────────────────────────────────────────────────────────────

DO_PUSH=0
if [[ "${1:-}" == "--push" ]]; then DO_PUSH=1; fi

say() { printf '\033[1;36m==>\033[0m %s\n' "$*"; }
ok()  { printf '\033[1;32m ✓ \033[0m %s\n' "$*"; }
die() { printf '\033[1;31m ✗ \033[0m %s\n' "$*" >&2; exit 1; }

command -v git >/dev/null 2>&1 || die "git not found in PATH"

say "1/5  Cloning source (shallow-avoided to preserve history)..."
rm -rf "$WORK_DIR"
git clone --branch "$SOURCE_BRANCH" --single-branch "$SOURCE_REPO" "$WORK_DIR"
cd "$WORK_DIR"
ok "Cloned $(git rev-parse --short HEAD) into $WORK_DIR"

say "2/5  Extracting '$SUBTREE_PREFIX' into branch '$EXTRACT_BRANCH'..."
git subtree split --prefix="$SUBTREE_PREFIX" -b "$EXTRACT_BRANCH" >/dev/null
COMMIT_COUNT=$(git rev-list --count "$EXTRACT_BRANCH")
ok "Extracted $COMMIT_COUNT commit(s)"

say "3/5  Preview — top 10 commits on extracted branch:"
git --no-pager log --oneline -n 10 "$EXTRACT_BRANCH"
echo "  ... (+ $((COMMIT_COUNT > 10 ? COMMIT_COUNT - 10 : 0)) older commits)"
echo

say "3/5  Preview — files at root of extracted tree:"
git --no-pager ls-tree --name-only "$EXTRACT_BRANCH" | column
echo

say "4/5  Verifying no 'wp-theme/' prefix leaked into extracted history..."
if git --no-pager log "$EXTRACT_BRANCH" --name-only --pretty=format: | grep -q '^wp-theme/'; then
	die "leak detected — aborting"
fi
ok "extracted history is clean"

if [[ "$DO_PUSH" -eq 0 ]]; then
	echo
	say "5/5  Dry run complete."
	echo "    To actually push, run ONE of:"
	echo
	echo "    # via this script:"
	echo "    bash $(basename "$0") --push"
	echo
	echo "    # or manually (lets you inspect first):"
	echo "    cd $WORK_DIR"
	echo "    git push $TARGET_REPO $EXTRACT_BRANCH:$TARGET_BRANCH"
	echo
	echo "    Nothing has been pushed. Safe to discard $WORK_DIR if you want to start over."
	exit 0
fi

say "5/5  Pushing $EXTRACT_BRANCH → $TARGET_REPO $TARGET_BRANCH..."
# Sanity: confirm target branch doesn't already exist (we expect an empty repo).
if git ls-remote --heads "$TARGET_REPO" "$TARGET_BRANCH" 2>/dev/null | grep -q .; then
	die "target $TARGET_REPO already has a '$TARGET_BRANCH' branch. Aborting — use --force manually if this is intentional."
fi
git push "$TARGET_REPO" "$EXTRACT_BRANCH:$TARGET_BRANCH"
ok "Pushed. Target: $TARGET_REPO ($TARGET_BRANCH)"

echo
say "Done. Next manual steps:"
echo "  1. Visit the target repo and confirm files are at the root."
echo "  2. On the target repo → Settings → General, set default branch = $TARGET_BRANCH."
echo "  3. Optional: delete the 'wp-theme/' subtree from renoshell if you don't want it there anymore:"
echo "       git rm -rf wp-theme/ && git commit -m 'move theme to dedicated wordpress repo' && git push"
