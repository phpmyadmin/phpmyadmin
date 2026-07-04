FROM php:8.3-cli
RUN docker-php-ext-install mysqli
WORKDIR /var/www/html
EXPOSE 8080
# Sessions are stored on the bind mount so a recorded session (cookie + CSRF
# token) is still valid when Keploy replays the suite against a fresh
# container. GC is disabled so the session fixture never expires.
CMD ["php", \
    "-d", "session.save_path=/var/www/html/.keploy-sessions", \
    "-d", "session.gc_probability=0", \
    "-d", "session.gc_maxlifetime=315360000", \
    "-S", "0.0.0.0:8080", "-t", "public"]
