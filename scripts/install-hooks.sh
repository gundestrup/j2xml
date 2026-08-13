#!/usr/bin/env bash
#
# Install git hooks for the J2XML repository.
#
# Symlinks the version-controlled hooks from scripts/git-hooks/ into .git/hooks/
# so they are active in the local working copy.
#
set -euo pipefail

repo_root=$(git rev-parse --show-toplevel 2>/dev/null || true)
if [ -z "$repo_root" ]; then
	echo "Error: not inside a git repository." >&2
	exit 1
fi

hooks_dir="$repo_root/.git/hooks"
source_dir="$repo_root/scripts/git-hooks"

mkdir -p "$hooks_dir"

for hook in "$source_dir"/*; do
	[ -f "$hook" ] || continue
	hook_name=$(basename "$hook")
	target="$hooks_dir/$hook_name"

	# Make the source executable.
	chmod +x "$hook"

	# Remove existing hook (file or symlink) and create a fresh symlink.
	if [ -e "$target" ] || [ -L "$target" ]; then
		rm -f "$target"
	fi
	ln -s "../../scripts/git-hooks/$hook_name" "$target"

	echo "Installed: $hook_name → scripts/git-hooks/$hook_name"
done

echo ""
echo "Git hooks installed. They will run automatically on git commit."
echo "To bypass: git commit --no-verify"
echo "To update: re-run this script after pulling changes to scripts/git-hooks/"
