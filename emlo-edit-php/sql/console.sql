-- Extract catalogues associated (linked to) from
-- Hartlib and Comenius


-- links:
--    Person -> created -> Work
--    Work -> was_addressed_to -> Person
--    Work -> mentions -> Person

-- hartlib: cofk_import_hartlib_people-name:000000449
select * from cofk_union_person
where iperson_id='300446';

-- Comenius: cofk_import_comenius-author:000000001
select * from cofk_union_person
where iperson_id='200001';

select count(*) from cofk_union_relationship -- 3854 letters
where (left_id_value = 'cofk_import_hartlib_people-name:000000449' OR
       right_id_value = 'cofk_import_hartlib_people-name:000000449') AND
      (left_table_name='cofk_union_work' or right_table_name='cofk_union_work');

select * from cofk_union_relationship -- 977 letters
where (left_id_value = 'cofk_import_comenius-author:000000001' OR
       right_id_value = 'cofk_import_comenius-author:000000001') AND
      (left_table_name='cofk_union_work' or right_table_name='cofk_union_work');


select DISTINCT original_catalogue, count(original_catalogue)  from cofk_union_work
WHERE work_id in (
  select work_id from (
                        select relationship_type, right_id_value as work_id from cofk_union_relationship
                        where left_id_value = 'cofk_import_hartlib_people-name:000000449' AND relationship_type = 'created'
                        UNION
                        select relationship_type, left_id_value as work_id from cofk_union_relationship
                        where right_id_value = 'cofk_import_hartlib_people-name:000000449' AND relationship_type = 'was_addressed_to'
                        UNION
                        select relationship_type, left_id_value as work_id from cofk_union_relationship
                        where right_id_value = 'cofk_import_hartlib_people-name:000000449' AND relationship_type = 'mentions'
                      ) relations
) group by original_catalogue
order by original_catalogue;


select DISTINCT original_catalogue, count(original_catalogue)  from cofk_union_work
WHERE work_id in (
  select work_id from (
    select relationship_type, right_id_value as work_id from cofk_union_relationship
    where left_id_value = 'cofk_import_comenius-author:000000001' AND relationship_type = 'created'
    UNION
    select relationship_type, left_id_value as work_id from cofk_union_relationship
    where right_id_value = 'cofk_import_comenius-author:000000001' AND relationship_type = 'was_addressed_to'
    --UNION
    --select relationship_type, left_id_value as work_id from cofk_union_relationship
    --where right_id_value = 'cofk_import_comenius-author:000000001' AND relationship_type = 'mentions'
  ) relations
) group by original_catalogue
order by original_catalogue;

