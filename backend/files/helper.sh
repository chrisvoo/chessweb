#!/bin/bash

function log_info {
  log 'INFO   ' "$1"
}

function log_error {
  local log_color
  log_color=$(tput setaf 1)
  log 'ERROR  ' "$1" "$log_color"
}

function log_warn {
  local log_color
  log_color=$(tput setaf 3)
  log 'WARNING' "$1" "$log_color"
}

function log {
  local log_level=$1
  local log_message=$2
  local log_color=$3

  local log_date
  log_date=$(date +"%Y-%m-%dT%H:%M:%S%z")
  local log_prefix="[$log_level][$log_date]"

  printf "%40s\n" "${log_color}${log_prefix} $log_message $(tput sgr0)"
}

function show_spinner {
  pid=$!
  while kill -0 $pid 2> /dev/null; do
    printf '.'
    sleep .2
  done
  wait "$pid"
  return $?
}
