FROM solr:8

COPY solrconfig.xml /opt/solr
COPY schema.xml /opt/solr

#COPY entrypoint /opt/solr/start

#USER root
#RUN chmod +x /opt/solr/start

CMD ["solr-foreground"]

#ENTRYPOINT ["/opt/solr/start"]