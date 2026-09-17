FROM php:7.4-apache

# Certificados raiz atualizados (necessários para o TLS da Aiven)
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Extensões do PHP
RUN docker-php-ext-install pdo pdo_mysql

# Habilita mod_rewrite do Apache
RUN a2enmod rewrite

# Define o ServerName para eliminar o aviso AH00558 no log
RUN printf 'ServerName localhost\n' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

# Permite .htaccess na raiz do servidor
RUN printf '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/helpdesk.conf \
    && a2enconf helpdesk

# Impede que arquivos sensíveis sejam servidos pelo Apache
RUN printf '<FilesMatch "^\\.env|\\.pem$">\n\
    Require all denied\n\
</FilesMatch>\n' > /etc/apache2/conf-available/protege-arquivos.conf \
    && a2enconf protege-arquivos

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80