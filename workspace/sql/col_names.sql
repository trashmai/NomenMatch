/* 有效名 */
select original_id, original_id, concat(genus, ' ', species, ' ', infraspecies), nk.hash, nk.hash, s.family, s.order, s.class, s.phylum, s.kingdom from taxon t join _search_scientific s on t.id=s.id join _natural_keys nk on nk.id=t.id where nk.name_code is not null INTO OUTFILE '/tmp/col_names.csv'  FIELDS TERMINATED BY '\t'  LINES TERMINATED BY '\n';
