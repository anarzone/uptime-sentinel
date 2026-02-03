FROM nginx:alpine

# Copy custom nginx configuration
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Create directory for PHP-FPM socket if needed
RUN mkdir -p /var/run/php

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
