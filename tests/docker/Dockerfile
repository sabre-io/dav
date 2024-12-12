FROM ubuntu:24.04

# Install PHP
RUN apt update -y
RUN apt install ca-certificates -y
ADD php.sources /etc/apt/sources.list.d/
RUN apt update -y
RUN apt install -y php7.4 php7.4-xml php7.4-mbstring php7.4-curl php7.4-sqlite3
RUN apt install -y curl zip git

# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --version=2.8.3
RUN mv composer.phar /usr/local/bin/composer
RUN rm composer-setup.php

# Setup tests
RUN mkdir /src/
ADD run-tests.sh .
RUN chmod +x run-tests.sh

ENTRYPOINT ["/run-tests.sh"]

# docker run -ti -v /mnt/d/temp/repos/sabre-dav:/src:rw sabre-dav-unit-tests:latest /bin/bash