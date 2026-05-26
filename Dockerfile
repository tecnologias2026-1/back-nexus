FROM php:8.2-cli

WORKDIR /app
COPY . .

# Instala dependencias del sistema necesarias para compilar la extensión mysqli
RUN apt-get update \
	&& apt-get install -y --no-install-recommends default-libmysqlclient-dev build-essential \
	&& docker-php-ext-install mysqli \
	&& docker-php-ext-enable mysqli \
	&& rm -rf /var/lib/apt/lists/*

EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:$PORT -t ."]



