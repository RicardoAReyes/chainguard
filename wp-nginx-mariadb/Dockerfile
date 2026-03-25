FROM cgr.dev/chainguard-private/wordpress:latest-dev

USER root

#Chainguard OS Packages 
#php-8.4-gd                      │ Image manipulation (alternative/supplement to imagick)   
#php-8.4-xdebug                  │ Local debugging only (never in prod)    

RUN apk add --no-cache \
    php-8.4-gd \
    php-8.4-xdebug

#Non Chainguard Packages
#wp-cli                          │ WP-CLI for management/automation 

RUN wget -O /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x /usr/local/bin/wp

USER 65532
