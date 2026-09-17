FROM php:7.4-apache

# Instala extensões necessárias para MySQL/MariaDB
RUN docker-php-ext-install pdo pdo_mysql

# Habilita mod_rewrite do Apache
RUN a2enmod rewrite

# Configura o Apache para permitir .htaccess na raiz do servidor
RUN printf '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/helpdesk.conf \
    && a2enconf helpdesk

# Define a pasta da aplicação
WORKDIR /var/www/html

# Copia o projeto para dentro do container
COPY . /var/www/html

# Ajusta permissões para o Apache conseguir ler os arquivos
RUN chown -R www-data:www-data /var/www/html

# Informa que o container roda na porta 80
EXPOSE 80
