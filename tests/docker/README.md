USAGE
=====

- Install Docker

REPO
----

- Download the folder **/tests/docker** from the repository
- Goto that folder using a terminal
- Run `docker compose up`
- Run `docker run --rm -ti sabre-dav-unit-tests`

LOCAL
-----

- Goto **$local_repo_path/tests/docker** using a terminal
- Run `docker compose up`
- Run either:
    - `docker run --rm -ti -v $local_repo_path:/test-dir/ sabre-dav-unit-tests`
    - `./run-local.sh`


DEV
---

- Apply changes to Dockerfile
- Run `docker compose up --build`