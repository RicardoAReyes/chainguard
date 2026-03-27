FROM cgr.dev/chainguard-private/wordpress:latest-dev

USER root

#Chainguard OS Packages 
#php-8.4-gd                      │ Image manipulation (alternative/supplement to imagick)   
#php-8.4-xdebug                  │ Local debugging only (never in prod)    
#php-8.4-phar                    | PHAR (PHP Archive) files to package entire applications or libraries into a single file for easy distribution, deployment, and execution, functioning similarly to JAR files in Java. 
RUN apk add --no-cache \
    php-8.4-gd \
    php-8.4-phar \
    php-8.4-xdebug

#Non Chainguard Packages
#wp-cli                          │ WP-CLI for management/automation 

RUN wget -O /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x /usr/local/bin/wp

USER 65532
