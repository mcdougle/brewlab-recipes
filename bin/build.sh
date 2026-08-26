#!/usr/bin/env bash
#------------------------------------------------------------------------------
#   build.sh
#------------------------------------------------------------------------------
# Packages a distributable plugin zip via `git archive`, which only ever
# includes git-tracked files — anything gitignored (.claude/, etc.) is
# excluded automatically, and files marked export-ignore in .gitattributes
# (README.md, bin/, ...) are stripped even though they're tracked. Run from
# anywhere; requires a git repo with at least one commit.
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

plugin_file="brewlab-recipes.php"
slug="brewlab-recipes"

if [ ! -d .git ]; then
	echo "error: no .git directory found in $repo_root — nothing to archive yet." >&2
	exit 1
fi

version="$(grep -m1 '^ \* Version:' "$plugin_file" | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
if [ -z "$version" ]; then
	echo "error: could not read Version header from $plugin_file" >&2
	exit 1
fi

mkdir -p build
out="build/${slug}-${version}.zip"

git archive --format=zip --prefix="${slug}/" -o "$out" HEAD

echo "built $out"
