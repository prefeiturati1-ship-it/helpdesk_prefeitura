FROM php:7.4-apache

# Instala extensões necessárias para MySQL/MariaDB
RUN docker-php-ext-install pdo pdo_mysql

# Habilita mod_rewrite do Apache
RUN a2enmod rewrite

# Configura o Apache para permitir .htaccess
RUN printf '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/helpdesk.conf \
    && a2enconf helpdesk

# Cria o Alias para manter o mesmo caminho usado no XAMPP
RUN printf 'Alias /helpdesk_prefeitura /var/www/html\n\
\n\
<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/helpdesk-alias.conf \
    && a2enconf helpdesk-alias

# Define a pasta da aplicação
WORKDIR /var/www/html

# Copia o projeto para dentro do container
COPY . /var/www/html

# Ajusta permissões
RUN chown -R www-data:www-data /var/www/html