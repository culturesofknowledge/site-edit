select * from cofk_union_queryable_work
where related_resources like 'Answered%Reply%';

select * from cofk_union_relationship
where relationship_type='is_reply_to';


select * from cofk_union_relationship
where relationship_type='is_replied_by';


DO
$work$
    declare iworkid varchar(100);
    BEGIN

      for iworkid in

        SELECT DISTINCT id
        FROM (
               SELECT left_id_value AS id
               FROM cofk_union_relationship
               WHERE relationship_type = 'matches'
               UNION
               SELECT right_id_value AS id
               FROM cofk_union_relationship
               WHERE relationship_type = 'matches'
             ) AS ref_ids

        loop
          perform dbf_cofk_union_refresh_queryable_work(iworkid);
        end loop;
    END
$work$;


SELECT DISTINCT id
FROM (
       SELECT left_id_value AS id
       FROM cofk_union_relationship
       WHERE relationship_type = 'matches'
       UNION
       SELECT right_id_value AS id
       FROM cofk_union_relationship
       WHERE relationship_type = 'matches'
     ) AS ref_ids;

select * from cofk_union_relationship
where relationship_type='matches';