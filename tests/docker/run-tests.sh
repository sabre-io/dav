#!/bin/bash

repoUrl=
while [ -z "$repoUrl" ]
do
  read -p "Please enter public repository URL:" repoUrl
  
  if [ -z "$repoUrl" ]; then echo "Repository URL is mandatory"; fi
done

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

echo "Starting setup"

git clone -b "$branchName" --single-branch --depth 1 "$repoUrl" /src/

(
  cd /src/
  composer install
  
  mkdir -p vendor/sabre/http/tests/www
  php -S localhost:8000 -t vendor/sabre/http/tests/www &
  
  composer phpunit
)
