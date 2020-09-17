FROM solr:8

# taxa config directory
COPY ./compose/solr/taxa-conf taxa-conf

# init solr data
COPY ./compose/solr/init-data /var/solr/data


CMD ["solr-foreground"]