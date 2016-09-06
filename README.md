# NomeMatch
  
NomenMatch is a tool for taxonomists to match a set of species names against other sets with certain authority. It returns matched names, ids, usage status, and links to original sources. So not only the matched results, users can also know about differences of name usage among different sources.
This is important because we believe that there is no absolute right or wrong, and only different perspectives in taxonomy. This tool provides easily references for taxonomists.

The matching algorithm is derived from taxamatch of Tony Rees (http://www.cmar.csiro.au/datacentre/taxamatch.htm), with some adjustment in workflow and parameters. The major change is that NomenMatch can handle trinomial names.
We developed our own name similarity calculation function, based on [levenshtein distance](https://en.wikipedia.org/wiki/Levenshtein_distance) and cross-ranked comparison (e.g. species to subspecies and vise versa) to make sure the order of matched results make sense to taxonomists. 

Installation
------
Download NomenMatch code and put it to a web accessible folder, for example
```
/var/www/html/nomenmatch/
```

Dependency
------
- Http Server
- PHP 5+
- JAVA 7+
- Solr 4

Download and run solr 4 (http://archive.apache.org/dist/lucene/solr/4.9.1/) with a core using schema.xml and solrconfig.xml in conf/solr-config  
(It's should also work with other versions of solr with appropriate adjustment to schema.xml and solrconfig.xml)  

Quick start a solr instance compatible to NomenMatch:
-----
- Download and extract solr 4.9.1  
- copy schema.xml and solrconfig.xml in conf/solr-config to [extracted solr]/example/solr/collection1/conf/  
- cd to [extracted solr]/example/ and run  
```
java -jar start.jar
```
The endpoint will be:
```
http://localhost:8983/solr
```

How to stop running:  
```
ps aux | grep java
kill [pid]
```
That's it!  

Set Solr Endpoint URL
-----
Edit conf/solr_endpoint, enter your solr endpoint without trailing newline (\r\n) nor backslash (/), for example
```
http://localhost:8983/solr/taxa
```

Import data to solr
-----
Under workspace dir, run  
```
php importChecklistToSolr.php {/path/to/source_data.csv} [source_id]
```
if [source_id] is empty, \"source_data\" will be used as the source_id  

Source data format
-----
Tab seperated, see workspace/data/example.csv  
Column definition:
- namecode
- accepted_namecode
- scientific_name (full name or canonical form is ok)
- name_url_id (the id which can be used to create a valid url to a taxon name page)
- accepted_name_url_id (the id which can be used to create a valid url to an accepted taxon name page, if the name is a synonym)
- family
- order
- class
- phylum
- kingdom

Describe source data
-----
Edit conf/sources.example.csv and rename to sources.csv  
Column definition:  
- source_id
- source_name
- url_base (when combined with [accepted] name_url_id, it becomes valid url for the taxon, blah blah)  
for example,
- citation format
- source data page
- date (of source data fetched, downloaded, or created)

Delete source data in solr
-----
Under workspace dir, run  
```
php clean_source.php {source_id}
```  
to removee a specific source from solr, or run  
```
php clean_source.php all
```
to remove all sources at once.  
If this script doesn't work, usually it means java heap space out of memory. Try to restart solr and then run again.  

Demo
-----
http://match.taibif.tw

License
-----
This project is licensed under the terms of the GPLv3 (http://www.gnu.org/licenses/gpl-3.0.en.html) license.
