#!/bin/bash

function executeTests()
# $1 = Source directory
{
  src="$1"
  (
    cd "$src"
    composer install
    
    mkdir -p vendor/sabre/http/tests/www
    php -S localhost:8000 -t vendor/sabre/http/tests/www &
    
    composer phpunit
  )
}

function downloadRepo()
{
  local repoUrl=
  while [ -z "$repoUrl" ]
  do
    read -p "Please enter public repository URL:" repoUrl
    
    if [ -z "$repoUrl" ]; then echo "Repository URL is mandatory"; fi
  done
  
  local branchName=
  read -p "Enter the branch name [default: master]" branchName
  branchName=${branchName:-master}
  
  echo "Are you sure you want to test with?"
  echo "URL: $repoUrl"
  echo "Branch: $branchName"
  read -p "[y/N]" confirm
  
  if [ "$confirm" != "Y" ] && [ "$confirm" != "y" ]; then
    echo "Cancelling test run"
    exit
  fi
  
  git clone -b "$branchName" --single-branch --depth 1 "$repoUrl" /src/
}

echo "Starting test container"

src=/test-dir/
if [ -d $src ]; then
  echo "Using local volume" 
else
  src=/src/
  echo "Using repository"
  downloadRepo
fi

executeTests "$src"
