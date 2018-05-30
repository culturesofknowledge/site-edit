--
-- PostgreSQL database dump
--

-- Dumped from database version 9.6.8
-- Dumped by pg_dump version 9.6.8

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


--
-- Name: uuid-ossp; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA public;


--
-- Name: EXTENSION "uuid-ossp"; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION "uuid-ossp" IS 'generate universally unique identifiers (UUIDs)';


--
-- Name: cofk_union_rel_one_side; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.cofk_union_rel_one_side AS (
	table_name character varying(100),
	id_value character varying(100),
	start_date date,
	end_date date
);


ALTER TYPE public.cofk_union_rel_one_side OWNER TO postgres;

--
-- Name: cofk_common_get_orig_column_name(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.cofk_common_get_orig_column_name(text_id character varying) RETURNS character varying
    LANGUAGE plpgsql
    AS $$

begin
  return substr( text_id, strpos( text_id, '-' ) + 1, strpos( text_id, ':' ) - strpos( text_id, '-' ) - 1 );
end;

$$;


ALTER FUNCTION public.cofk_common_get_orig_column_name(text_id character varying) OWNER TO postgres;

--
-- Name: cofk_common_get_orig_id(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.cofk_common_get_orig_id(text_id character varying) RETURNS integer
    LANGUAGE plpgsql
    AS $$

begin
  return substr( text_id, strpos( text_id, ':' )+1)::integer ;
end;

$$;


ALTER FUNCTION public.cofk_common_get_orig_id(text_id character varying) OWNER TO postgres;

--
-- Name: cofk_common_get_orig_table_name(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.cofk_common_get_orig_table_name(text_id character varying) RETURNS character varying
    LANGUAGE plpgsql
    AS $$

begin
  return substr( text_id, 1, strpos( text_id, '-' ) - 1 );
end;

$$;


ALTER FUNCTION public.cofk_common_get_orig_table_name(text_id character varying) OWNER TO postgres;

--
-- Name: cofk_common_make_text_id(character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.cofk_common_make_text_id(orig_table_name character varying, orig_column_name character varying, orig_row_id integer) RETURNS character varying
    LANGUAGE plpgsql
    AS $$

begin
  return trim( orig_table_name ) || '-' || trim( orig_column_name ) || ':' 
         || lpad( orig_row_id::varchar, 9, '0' );
end;

$$;


ALTER FUNCTION public.cofk_common_make_text_id(orig_table_name character varying, orig_column_name character varying, orig_row_id integer) OWNER TO postgres;

--
-- Name: dbf_alphanumeric(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_alphanumeric(string_parm text) RETURNS text
    LANGUAGE plpgsql STRICT
    AS $$

begin
  return dbf_alphanumeric( string_parm,
                           FALSE,  -- don't include underscores 
                           ''      -- don't convert any other characters to underscores
                         );

end;

$$;


ALTER FUNCTION public.dbf_alphanumeric(string_parm text) OWNER TO postgres;

--
-- Name: dbf_alphanumeric(text, boolean, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_alphanumeric(string_parm text, allow_underscores boolean, convert_chars text) RETURNS text
    LANGUAGE plpgsql STRICT
    AS $$

declare
  alphanum text;
  string_length integer;
  i integer;
  one_char varchar(1);
  last_char varchar(1);
begin

  alphanum = '';
  last_char = '';
  string_length = length( string_parm );

  for i in 1..string_length loop
    one_char = substr( string_parm, i, 1 );

    if ( one_char >= 'a' and one_char <= 'z' )
    or ( one_char >= 'A' and one_char <= 'Z' )
    or ( one_char >= '0' and one_char <= '9' )
    then
      alphanum = alphanum || one_char;

    elsif one_char = '_' and allow_underscores then
      alphanum = alphanum || one_char;

    elsif strpos( convert_chars, one_char ) > 0 then
      one_char = '_';
      if last_char != '_' then -- don't have multiple underscores in a row
        alphanum = alphanum || one_char;
      end if;
    end if;

    last_char = one_char;
  end loop;

  return alphanum;
end;

$$;


ALTER FUNCTION public.dbf_alphanumeric(string_parm text, allow_underscores boolean, convert_chars text) OWNER TO postgres;

--
-- Name: dbf_alphanumeric_and_underscore(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_alphanumeric_and_underscore(string_parm text) RETURNS text
    LANGUAGE plpgsql STRICT
    AS $$

begin
  return dbf_alphanumeric( string_parm,
                           TRUE,  -- allow underscores
                           ''     -- don't convert any other characters to underscores
                          );
end;

$$;


ALTER FUNCTION public.dbf_alphanumeric_and_underscore(string_parm text) OWNER TO postgres;

--
-- Name: dbf_alphanumeric_with_conv_to_underscore(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_alphanumeric_with_conv_to_underscore(string_parm text, convert_chars text) RETURNS text
    LANGUAGE plpgsql STRICT
    AS $$

begin
  return dbf_alphanumeric( string_parm,
                           TRUE,            -- allow underscores
                           convert_chars    -- convert specified characters to underscores
                         );
end;

$$;


ALTER FUNCTION public.dbf_alphanumeric_with_conv_to_underscore(string_parm text, convert_chars text) OWNER TO postgres;

--
-- Name: dbf_alphanumeric_with_others_to_underscore(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_alphanumeric_with_others_to_underscore(string_parm text) RETURNS text
    LANGUAGE plpgsql STRICT
    AS $_$

declare
  backslash constant varchar(1) = E'\\';
  bad_chars varchar(100);
begin
  -- We need to add pound sign in the string below. (At the moment not correctly converted to UTF-8)
  bad_chars = ' !"$%^&*()-+={}[]:;@''~#|<,>.?/	' || backslash;

  return dbf_alphanumeric_with_conv_to_underscore( string_parm, bad_chars );
end;

$_$;


ALTER FUNCTION public.dbf_alphanumeric_with_others_to_underscore(string_parm text) OWNER TO postgres;

--
-- Name: dbf_alphanumeric_with_space_slash_to_underscore(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_alphanumeric_with_space_slash_to_underscore(string_parm text) RETURNS text
    LANGUAGE plpgsql STRICT
    AS $$

declare
  backslash constant varchar(1) = E'\\';
  bad_chars varchar(10);
begin
  bad_chars = ' /	' || backslash;
  return dbf_alphanumeric_with_conv_to_underscore( string_parm, bad_chars );
end;

$$;


ALTER FUNCTION public.dbf_alphanumeric_with_space_slash_to_underscore(string_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_check_collect_tool_session(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_check_collect_tool_session(input_username text, input_session_code text) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  timeout_minutes   constant integer := 90;

  session_ok        constant integer := 1;
  session_timed_out constant integer := -1;
  session_not_found constant integer := -2;
  
  last_action timestamp;
  interval_string   varchar(12);
begin
  select session_timestamp
  into last_action
  from cofk_collect_tool_session
  where username = input_username
  and session_code = input_session_code;

  if last_action is null then
    return session_not_found;
  end if;

  interval_string = timeout_minutes::varchar || ' minutes';

  if last_action < current_timestamp - interval_string::interval then
    return session_timed_out;
  end if;
    
  return session_ok;
end;

$$;


ALTER FUNCTION public.dbf_cofk_check_collect_tool_session(input_username text, input_session_code text) OWNER TO postgres;

--
-- Name: dbf_cofk_check_login_creds(text, text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_check_login_creds(input_username text, input_pw text, input_token text) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  login_success     constant integer :=  1;
  account_suspended constant integer := -1;
  invalid_creds     constant integer :=  0;    -- invalid username or password

  max_failed_logins_allowed constant integer := 10;

  decoded_username varchar(30);
  password_match_string text;
  active_user integer;
  failure_count integer;

  prev_login_var timestamp;
begin
  --------------------------------------
  -- See if a valid username was entered
  --------------------------------------
  select dbf_cofk_decode_username( input_username, input_token )
  into decoded_username;

  -------------------------
  -- Username wrong: return
  -------------------------
  if decoded_username is null then
    return invalid_creds;
  end if;

  ------------------------------------
  -- Username OK: what about password?
  ------------------------------------
  select active, md5( pw || input_token ), login_time
  into active_user, password_match_string, prev_login_var
  from cofk_users
  where username = decoded_username;

  -------------------------------------------------------------
  -- Username and password OK: save details of successful login
  -------------------------------------------------------------
  if password_match_string = input_pw and active_user = 1 then
    update cofk_users
    set 
      failed_logins = 0, 
      login_time = current_timestamp,
      prev_login = prev_login_var
    where 
      username = decoded_username;
   
    return login_success;

  -----------------------------------
  -- Account suspended: simply return
  -----------------------------------
  elseif active_user = 0 then
    return account_suspended;

  -------------------------------------------------------------
  -- Password wrong. 
  -- Increment login failures and if necessary suspend account.
  -------------------------------------------------------------
  elseif password_match_string != input_pw then
    update cofk_users
    set failed_logins = failed_logins + 1
    where username = decoded_username;

    select failed_logins
    into failure_count
    from cofk_users
    where username = decoded_username;

    if failure_count > max_failed_logins_allowed then
      update cofk_users
      set active = 0
      where username = decoded_username;
    end if;

    return invalid_creds;    -- invalid username or password
  end if;

  return invalid_creds;
end;

$$;


ALTER FUNCTION public.dbf_cofk_check_login_creds(input_username text, input_pw text, input_token text) OWNER TO postgres;

--
-- Name: dbf_cofk_check_session(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_check_session(input_username text, input_session_code text) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  timeout_hours   constant integer := 8;

  session_ok        constant integer := 1;
  session_timed_out constant integer := -1;
  session_not_found constant integer := -2;
  
  last_action timestamp;
  interval_string   varchar(12);
begin
  select session_timestamp
  into last_action
  from cofk_sessions
  where username = input_username
  and session_code = input_session_code;

  if last_action is null then
    return session_not_found;
  end if;

  interval_string = timeout_hours::varchar || ' hours';

  if last_action < current_timestamp - interval_string::interval then
    return session_timed_out;
  end if;
    
  return session_ok;
end;

$$;


ALTER FUNCTION public.dbf_cofk_check_session(input_username text, input_session_code text) OWNER TO postgres;

--
-- Name: dbf_cofk_collect_write_work_summary(integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_collect_write_work_summary(upload_id_parm integer, iwork_id_parm integer) RETURNS integer
    LANGUAGE plpgsql
    AS $$

declare
  decode text = '';
  results record;
  author_rec record;
  addressee_rec record;
  origin_rec record;
  destination_rec record;
  people_mentioned_rec record;
  places_mentioned_rec record;
  subj_rec record;
  lang_rec record;
  manif_rec record;
  manif_count integer not null := 0;
  repos_name text;
  resource_rec record;
begin
  -- SCCS version 1.3 2013/01/30 13:01:27

  delete from cofk_collect_work_summary 
  where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;

  select * into results from cofk_collect_work
  where upload_id = upload_id_parm and iwork_id = iwork_id_parm;

  -----------------------------------------------------
  -- Insert all columns that need no further processing
  -----------------------------------------------------
  insert into cofk_collect_work_summary (
    upload_id,
    work_id_in_tool,

    source_of_data,
    notes_on_letter,

    date_of_work_as_marked,
    notes_on_date_of_work,

    authors_as_marked,
    notes_on_authors,

    addressees_as_marked,
    notes_on_addressees,

    destination_as_marked,
    origin_as_marked,

    abstract,
    keywords,
    incipit,
    excipit,
    notes_on_people_mentioned,
    editors_notes
  )
  values (
    results.upload_id                    ,
    results.iwork_id                     ,

    results.accession_code               ,
    results.notes_on_letter              ,

    results.date_of_work_as_marked,
    results.notes_on_date_of_work        ,

    results.authors_as_marked            ,
    results.notes_on_authors             ,

    results.addressees_as_marked         ,
    results.notes_on_addressees          ,

    results.destination_as_marked        ,
    results.origin_as_marked             ,

    results.abstract                     ,
    results.keywords                     ,
    results.incipit                      ,
    results.excipit                      ,
    results.notes_on_people_mentioned    ,
    results.editors_notes
  );

  --------------
  -- Add date(s)
  --------------
  decode = '';
  if results.date_of_work_std_year > 0 or results.date_of_work_std_month > 0 or results.date_of_work_std_day > 0
  then
    if results.date_of_work_std_is_range > 0 and not 
    (results.date_of_work2_std_year > 0 or results.date_of_work2_std_month > 0 or results.date_of_work2_std_day > 0)
    then
      decode = 'From ';
    end if;

    if results.date_of_work_std_year > 0 then
      decode = decode || trim( to_char( results.date_of_work_std_year, '0000' ));
    else
      decode = decode || '????';
    end if;
    decode = decode || '-';
    if results.date_of_work_std_month > 0 then
      decode = decode || trim( to_char( results.date_of_work_std_month, '00' ));
    else
      decode = decode || '??';
    end if;
    decode = decode || '-';
    if results.date_of_work_std_day > 0 then
      decode = decode || trim( to_char( results.date_of_work_std_day, '00' ));
    else
      decode = decode || '??';
    end if;
  end if;

  if results.date_of_work2_std_year > 0 or results.date_of_work2_std_month > 0 or results.date_of_work2_std_day > 0
  then
    if results.date_of_work_std_is_range > 0 then
      if decode > '' then
        decode = decode || ' to ';
      else
        decode = 'To ';
      end if;
    end if;

    if results.date_of_work2_std_year > 0 then
      decode = decode || trim( to_char( results.date_of_work2_std_year, '0000' ));
    else
      decode = decode || '????';
    end if;
    decode = decode || '-';
    if results.date_of_work2_std_month > 0 then
      decode = decode || trim( to_char( results.date_of_work2_std_month, '00' ));
    else
      decode = decode || '??';
    end if;
    decode = decode || '-';
    if results.date_of_work2_std_day > 0 then
      decode = decode || trim( to_char( results.date_of_work2_std_day, '00' ));
    else
      decode = decode || '??';
    end if;
  end if;

  if decode > '' then
    update cofk_collect_work_summary 
    set date_of_work = decode
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  ------------------------
  -- Add original calendar
  ------------------------
  if results.original_calendar = 'J' then
    decode = 'Julian';
  elsif results.original_calendar = 'G' then
    decode = 'Gregorian';
  else
    decode = 'Unknown';
  end if;

  update cofk_collect_work_summary 
  set original_calendar = decode
  where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;

  -----------------
  -- Add date flags
  -----------------
  if results.date_of_work_std_is_range > 0 then
    update cofk_collect_work_summary 
    set date_of_work_is_range = 'Date range'
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  if results.date_of_work_inferred > 0 then
    update cofk_collect_work_summary 
    set date_of_work_inferred = 'Date inferred'
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  if results.date_of_work_uncertain > 0 then
    update cofk_collect_work_summary 
    set date_of_work_uncertain = 'Date uncertain'
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  if results.date_of_work_approx > 0 then
    update cofk_collect_work_summary 
    set date_of_work_approx = 'Date approximate'
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  --------------
  -- Add authors
  --------------
  decode = '';
  for author_rec in select
    p.union_iperson_id, p.primary_name, p.editors_notes, 
    coalesce( p.date_of_birth_year::varchar,  '' ) as date_of_birth_start, 
    coalesce( p.date_of_birth2_year::varchar, '' ) as date_of_birth_end, 
    coalesce( p.date_of_death_year::varchar,  '' ) as date_of_death_start, 
    coalesce( p.date_of_death2_year::varchar, '' ) as date_of_death_end,
    coalesce( p.flourished_year::varchar,     '' ) as flourished_start, 
    coalesce( p.flourished2_year::varchar,    '' ) as flourished_end
  from
    cofk_collect_person p, cofk_collect_author_of_work a
  where
    p.iperson_id = a.iperson_id and p.upload_id = a.upload_id
  and
    a.upload_id = upload_id_parm and a.iwork_id = iwork_id_parm
  order by
    primary_name
  loop
    if decode > '' then
      decode = decode || '; ';
    end if;

    decode = decode || author_rec.primary_name;

    if author_rec.date_of_birth_start > '' and author_rec.date_of_birth_end > '' then
      decode = decode || ', born ' || author_rec.date_of_birth_start || '-' || author_rec.date_of_birth_end;
    elsif author_rec.date_of_birth_start > '' then
      decode = decode || ', born ' || author_rec.date_of_birth_start;
    elsif author_rec.date_of_birth_end > '' then
      decode = decode || ', born ' || author_rec.date_of_birth_end || ' or before';
    end if;

    if author_rec.date_of_death_start > '' and author_rec.date_of_death_end > '' then
      decode = decode || ', died ' || author_rec.date_of_death_start || '-' || author_rec.date_of_death_end;
    elsif author_rec.date_of_death_start > '' then
      decode = decode || ', died ' || author_rec.date_of_death_start;
    elsif author_rec.date_of_death_end > '' then
      decode = decode || ', died ' || author_rec.date_of_death_end || ' or before';
    end if;

    if author_rec.flourished_start > '' and author_rec.flourished_end > '' then
      decode = decode || ', fl. ' || author_rec.flourished_start || '-' || author_rec.flourished_end;
    elsif author_rec.flourished_start > '' then
      decode = decode || ', fl. ' || author_rec.flourished_start;
    elsif author_rec.date_of_birth_end > '' then
      decode = decode || ', fl. ' || author_rec.date_of_birth_end || ' or before';
    end if;

    if author_rec.union_iperson_id > 0 then
      decode = decode || ' [ID ' || author_rec.union_iperson_id || ']';
    else
      decode = decode || ' [new]';
    end if;

    if author_rec.editors_notes > '' then
      decode = decode || ' [editor''s notes: ' || author_rec.editors_notes || ']';
    end if;
  end loop;

  if decode > '' then
    update cofk_collect_work_summary 
    set authors = decode
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  -------------------
  -- Add author flags
  -------------------
  if results.authors_inferred > 0 then
    update cofk_collect_work_summary 
    set authors_inferred = 'Author inferred'
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  if results.authors_uncertain > 0 then
    update cofk_collect_work_summary 
    set authors_uncertain = 'Author uncertain'
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  --------------
  -- Add addressees
  --------------
  decode = '';
  for addressee_rec in select
    p.union_iperson_id, p.primary_name, p.editors_notes,
    coalesce( p.date_of_birth_year::varchar,  '' ) as date_of_birth_start, 
    coalesce( p.date_of_birth2_year::varchar, '' ) as date_of_birth_end,
    coalesce( p.date_of_death_year::varchar,  '' ) as date_of_death_start, 
    coalesce( p.date_of_death2_year::varchar, '' ) as date_of_death_end,
    coalesce( p.flourished_year::varchar,     '' ) as flourished_start, 
    coalesce( p.flourished2_year::varchar,    '' ) as flourished_end

  from
    cofk_collect_person p, cofk_collect_addressee_of_work a
  where
    p.iperson_id = a.iperson_id and p.upload_id = a.upload_id
  and
    a.upload_id = upload_id_parm and a.iwork_id = iwork_id_parm
  order by
    primary_name
  loop
    if decode > '' then
      decode = decode || '; ';
    end if;

    decode = decode || addressee_rec.primary_name;

    if addressee_rec.date_of_birth_start > '' and addressee_rec.date_of_birth_end > '' then
      decode = decode || ', born ' || addressee_rec.date_of_birth_start || '-' || addressee_rec.date_of_birth_end;
    elsif addressee_rec.date_of_birth_start > '' then
      decode = decode || ', born ' || addressee_rec.date_of_birth_start;
    elsif addressee_rec.date_of_birth_end > '' then
      decode = decode || ', born ' || addressee_rec.date_of_birth_end || ' or before';
    end if;

    if addressee_rec.date_of_death_start > '' and addressee_rec.date_of_death_end > '' then
      decode = decode || ', died ' || addressee_rec.date_of_death_start || '-' || addressee_rec.date_of_death_end;
    elsif addressee_rec.date_of_death_start > '' then
      decode = decode || ', died ' || addressee_rec.date_of_death_start;
    elsif addressee_rec.date_of_death_end > '' then
      decode = decode || ', died ' || addressee_rec.date_of_death_end || ' or before';
    end if;

    if addressee_rec.flourished_start > '' and addressee_rec.flourished_end > '' then
      decode = decode || ', fl. ' || addressee_rec.flourished_start || '-' || addressee_rec.flourished_end;
    elsif addressee_rec.flourished_start > '' then
      decode = decode || ', fl. ' || addressee_rec.flourished_start;
    elsif addressee_rec.date_of_birth_end > '' then
      decode = decode || ', fl. ' || addressee_rec.date_of_birth_end || ' or before';
    end if;

    if addressee_rec.union_iperson_id > 0 then
      decode = decode || ' [ID ' || addressee_rec.union_iperson_id || ']';
    else
      decode = decode || ' [new]';
    end if;

    if addressee_rec.editors_notes > '' then
      decode = decode || ' [editor''s notes: ' || addressee_rec.editors_notes || ']';
    end if;
  end loop;

  if decode > '' then
    update cofk_collect_work_summary 
    set addressees = decode
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  ----------------------
  -- Add addressee flags
  ----------------------
  if results.addressees_inferred > 0 then
    update cofk_collect_work_summary 
    set addressees_inferred = 'Addressee inferred'
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  if results.addressees_uncertain > 0 then
    update cofk_collect_work_summary 
    set addressees_uncertain = 'Addressee uncertain'
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  -------------
  -- Add origin
  -------------
  decode = '';
  if results.origin_id > 0 then
		select 
			l.union_location_id, l.location_name, l.editors_notes into origin_rec
		from
			cofk_collect_location l
		where
			l.upload_id = upload_id_parm and l.location_id = results.origin_id;

    decode = decode || origin_rec.location_name;
    if origin_rec.union_location_id > 0 then
      decode = decode || ' [ID ' || origin_rec.union_location_id || ']';
    else
      decode = decode || ' [new]';
    end if;
    if origin_rec.editors_notes > '' then
      decode = decode || ' [editor''s notes: ' || origin_rec.editors_notes || ']';
    end if;

    update cofk_collect_work_summary 
    set origin = decode
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  -------------------
  -- Add origin flags
  -------------------
  if results.origin_inferred > 0 then
    update cofk_collect_work_summary 
    set origin_inferred = 'Origin inferred'
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  if results.origin_uncertain > 0 then
    update cofk_collect_work_summary 
    set origin_uncertain = 'Origin uncertain'
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;


  ------------------
  -- Add destination
  ------------------
  decode = '';
  if results.destination_id > 0 then
		select 
			l.union_location_id, l.location_name, l.editors_notes into destination_rec
		from
			cofk_collect_location l
		where
			l.upload_id = upload_id_parm and l.location_id = results.destination_id;

    decode = decode || destination_rec.location_name;
    if destination_rec.union_location_id > 0 then
      decode = decode || ' [ID ' || destination_rec.union_location_id || ']';
    else
      decode = decode || ' [new]';
    end if;
    if destination_rec.editors_notes > '' then
      decode = decode || ' [editor''s notes: ' || destination_rec.editors_notes || ']';
    end if;

    update cofk_collect_work_summary 
    set destination = decode
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  ------------------------
  -- Add destination flags
  ------------------------
  if results.destination_inferred > 0 then
    update cofk_collect_work_summary 
    set destination_inferred = 'Destination inferred'
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  if results.destination_uncertain > 0 then
    update cofk_collect_work_summary 
    set destination_uncertain = 'Destination uncertain'
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;


  -----------------------
  -- Add people mentioned
  -----------------------
  decode = '';
  for people_mentioned_rec in select
    p.union_iperson_id, p.primary_name, p.editors_notes,
    coalesce( p.date_of_birth_year::varchar,  '' ) as date_of_birth_start, 
    coalesce( p.date_of_birth2_year::varchar, '' ) as date_of_birth_end,
    coalesce( p.date_of_death_year::varchar,  '' ) as date_of_death_start, 
    coalesce( p.date_of_death2_year::varchar, '' ) as date_of_death_end,
    coalesce( p.flourished_year::varchar,     '' ) as flourished_start, 
    coalesce( p.flourished2_year::varchar,    '' ) as flourished_end
  from
    cofk_collect_person p, cofk_collect_person_mentioned_in_work m
  where
    p.iperson_id = m.iperson_id and p.upload_id = m.upload_id
  and
    m.upload_id = upload_id_parm and m.iwork_id = iwork_id_parm
  order by
    primary_name
  loop
    if decode > '' then
      decode = decode || '; ';
    end if;

    decode = decode || people_mentioned_rec.primary_name;

    if people_mentioned_rec.date_of_birth_start > '' and people_mentioned_rec.date_of_birth_end > '' then
      decode = decode || ', born ' || people_mentioned_rec.date_of_birth_start || '-' || people_mentioned_rec.date_of_birth_end;
    elsif people_mentioned_rec.date_of_birth_start > '' then
      decode = decode || ', born ' || people_mentioned_rec.date_of_birth_start;
    elsif people_mentioned_rec.date_of_birth_end > '' then
      decode = decode || ', born ' || people_mentioned_rec.date_of_birth_end || ' or before';
    end if;

    if people_mentioned_rec.date_of_death_start > '' and people_mentioned_rec.date_of_death_end > '' then
      decode = decode || ', died ' || people_mentioned_rec.date_of_death_start || '-' || people_mentioned_rec.date_of_death_end;
    elsif people_mentioned_rec.date_of_death_start > '' then
      decode = decode || ', died ' || people_mentioned_rec.date_of_death_start;
    elsif people_mentioned_rec.date_of_death_end > '' then
      decode = decode || ', died ' || people_mentioned_rec.date_of_death_end || ' or before';
    end if;

    if people_mentioned_rec.flourished_start > '' and people_mentioned_rec.flourished_end > '' then
      decode = decode || ', fl. ' || people_mentioned_rec.flourished_start || '-' || people_mentioned_rec.flourished_end;
    elsif people_mentioned_rec.flourished_start > '' then
      decode = decode || ', fl. ' || people_mentioned_rec.flourished_start;
    elsif people_mentioned_rec.date_of_birth_end > '' then
      decode = decode || ', fl. ' || people_mentioned_rec.date_of_birth_end || ' or before';
    end if;


    if people_mentioned_rec.union_iperson_id > 0 then
      decode = decode || ' [ID ' || people_mentioned_rec.union_iperson_id || ']';
    else
      decode = decode || ' [new]';
    end if;

    if people_mentioned_rec.editors_notes > '' then
      decode = decode || ' [editor''s notes: ' || people_mentioned_rec.editors_notes || ']';
    end if;
  end loop;

  if decode > '' then
    update cofk_collect_work_summary 
    set people_mentioned = decode
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;


  -----------------------
  -- Add places mentioned
  -----------------------
  decode = '';
  for places_mentioned_rec in select
    l.union_location_id, l.location_name, l.editors_notes
  from
    cofk_collect_location l, cofk_collect_place_mentioned_in_work m
  where
    l.location_id = m.location_id and l.upload_id = m.upload_id
  and
    m.upload_id = upload_id_parm and m.iwork_id = iwork_id_parm
  order by
    location_name
  loop
    if decode > '' then
      decode = decode || '; ';
    end if;
    decode = decode || places_mentioned_rec.location_name;
    if places_mentioned_rec.union_location_id > 0 then
      decode = decode || ' [ID ' || places_mentioned_rec.union_location_id || ']';
    else
      decode = decode || ' [new]';
    end if;
    if places_mentioned_rec.editors_notes > '' then
      decode = decode || ' [editor''s notes: ' || places_mentioned_rec.editors_notes || ']';
    end if;
  end loop;

  if decode > '' then
    update cofk_collect_work_summary 
    set places_mentioned = decode
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;


  -----------------------
  -- Add languages
  -----------------------
  decode = '';
  for lang_rec in select
    i.language_name
  from
    iso_639_language_codes i, cofk_collect_language_of_work lw
  where
    i.code_639_3 = lw.language_code 
  and
    lw.upload_id = upload_id_parm and lw.iwork_id = iwork_id_parm
  order by
    language_name
  loop
    if decode > '' then
      decode = decode || '; ';
    end if;
    decode = decode || lang_rec.language_name;
  end loop;

  if results.language_of_work > '' then -- other languages not included in drop-down list
    if decode > '' then
      decode = decode || '; ';
    end if;
    decode = decode || results.language_of_work;
  end if;

  if decode > '' then
    update cofk_collect_work_summary 
    set languages_of_work = decode
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  -----------------------
  -- Add subjects
  -----------------------
  decode = '';
  for subj_rec in select
    s.subject_desc
  from
    cofk_union_subject s, cofk_collect_subject_of_work sw
  where
    s.subject_id = sw.subject_id 
  and
    sw.upload_id = upload_id_parm and sw.iwork_id = iwork_id_parm
  order by
    subject_desc
  loop
    if decode > '' then
      decode = decode || '; ';
    end if;
    decode = decode || subj_rec.subject_desc;
  end loop;

  if decode > '' then
    update cofk_collect_work_summary 
    set subjects_of_work = decode
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;

  -----------------------
  -- Add manifestations
  -----------------------
  decode = '';
  for manif_rec in select 
    m.*, d.document_type_desc
  from 
    cofk_collect_manifestation m, cofk_lookup_document_type d
  where
    m.manifestation_type = d.document_type_code
  and
    m.upload_id = upload_id_parm and m.iwork_id = iwork_id_parm
  order by
    m.manifestation_type, m.manifestation_id
  loop
    manif_count = manif_count + 1;
    if manif_count > 1 then
      decode = decode || ' -- ';
    end if;

    repos_name = '';
    decode = decode || manif_count::varchar || '. ' || manif_rec.document_type_desc || '.';

    if manif_rec.repository_id > 0 then
      select institution_name into repos_name
      from cofk_collect_institution
      where upload_id = upload_id_parm 
      and institution_id = manif_rec.repository_id;

      decode = decode || ' Repository: ' || repos_name || '.';
    end if;

    if manif_rec.id_number_or_shelfmark > '' then
      decode = decode || ' Shelfmark: ' || manif_rec.id_number_or_shelfmark || '.';
    end if;

    if manif_rec.printed_edition_details > '' then
      decode = decode || ' Printed edition details: ' || manif_rec.printed_edition_details || '.';
    end if;

    if manif_rec.image_filenames > '' then
      decode = decode || ' Image filenames or URLs: ' || manif_rec.image_filenames || '.';
    end if;

    if manif_rec.manifestation_notes > '' then
      decode = decode || ' Notes on document: ' || manif_rec.manifestation_notes || '.';
    end if;
  end loop;

  if decode > '' then
    update cofk_collect_work_summary 
    set manifestations = decode
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;
    
  ------------------------
  -- Add related resources
  ------------------------
  decode = '';
  for resource_rec in select 
    *
  from 
    cofk_collect_work_resource
  where
    upload_id = upload_id_parm and iwork_id = iwork_id_parm
  order by
    resource_id
  loop
    if decode >  '' then
      decode = decode || ' -- ';
    end if;

    decode = decode || resource_rec.resource_name;

    if resource_rec.resource_url > '' then
      decode = decode || ': ' || resource_rec.resource_url;
    end if;

    if resource_rec.resource_details > '' then
      decode = decode || '. Further details: ' || resource_rec.resource_details || '.';
    end if;
  end loop;

  if decode > '' then
    update cofk_collect_work_summary 
    set related_resources = decode
    where upload_id = upload_id_parm and work_id_in_tool = iwork_id_parm;
  end if;
    
  return iwork_id_parm;
end;

$$;


ALTER FUNCTION public.dbf_cofk_collect_write_work_summary(upload_id_parm integer, iwork_id_parm integer) OWNER TO postgres;

--
-- Name: dbf_cofk_create_sql_user(character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_create_sql_user(input_username character varying, grant_edit_role character varying) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  statement varchar(500);
  existing_user varchar(100);
begin
  if substr( input_username, 1, length( 'cofk' )) != 'cofk' then
    raise exception 'Username must begin with %', 'cofk';
  end if;

  select usename
  into existing_user
  from pg_user
  where usename = input_username;

  if FOUND then
    null; -- user already exists in another database
  else
    statement = 'create user ' || input_username;
    execute statement;
  end if;

  statement = 'grant viewer_role_' || 'cofk' || ' to ' || input_username;
  execute statement;

  if grant_edit_role = 'Y' then
    statement = 'grant editor_role_' || 'cofk' || ' to ' || input_username;
  end if;
  execute statement;

  return input_username;
end;

$$;


ALTER FUNCTION public.dbf_cofk_create_sql_user(input_username character varying, grant_edit_role character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_create_user(character varying, character varying, character varying, character varying, text, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_create_user(input_username character varying, input_password character varying, input_surname character varying, input_forename character varying, input_email text, grant_edit_role character varying) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  existing_username varchar(100);
begin
  if length( input_username ) <= length( 'cofk' ) then
    raise exception 'Invalid username.';
  end if;

  select username 
  into existing_username
  from cofk_users
  where trim(lower( username )) = trim(lower( input_username ));

  if FOUND then
    raise exception 'Invalid username.';
  end if;

  if input_password is null or trim( input_password ) = '' 
  or input_password = md5('') or input_password = md5( ' ' )
  or length( input_password ) != length( md5( ' ' )) -- try and check that the password is already in md5
  then
    raise exception 'Invalid password.';
  end if;

  if input_surname is null or trim( input_surname ) = '' then
    raise exception 'Surname cannot be blank.';
  end if;

  insert into cofk_USERS (
    username,
    pw,
    surname,
    forename,
    email
  )
  values (
    input_username,
    input_password,
    input_surname,
    coalesce( input_forename, '' ),
    input_email
  );

  return dbf_cofk_create_sql_user( input_username, grant_edit_role );
end;

$$;


ALTER FUNCTION public.dbf_cofk_create_user(input_username character varying, input_password character varying, input_surname character varying, input_forename character varying, input_email text, grant_edit_role character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_decode_username(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_decode_username(input_username text, input_token text) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  cleartext_username varchar(30);
begin
  select username
  into cleartext_username
  from cofk_users
  where md5( md5( username ) || input_token ) = input_username;
  
  return cleartext_username;
end;

$$;


ALTER FUNCTION public.dbf_cofk_decode_username(input_username text, input_token text) OWNER TO postgres;

--
-- Name: dbf_cofk_delete_collect_tool_session(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_delete_collect_tool_session(session_for_deletion text) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  failure constant integer := 0;
  success constant integer := 1;

  rows_affected integer := 0;
begin

  delete from cofk_collect_tool_session where session_code = session_for_deletion;

  get diagnostics rows_affected = row_count;
  if rows_affected != 1 then
    return failure;
  end if;

  return success;
end;

$$;


ALTER FUNCTION public.dbf_cofk_delete_collect_tool_session(session_for_deletion text) OWNER TO postgres;

--
-- Name: dbf_cofk_delete_session(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_delete_session(session_for_deletion text) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  failure constant integer := 0;
  success constant integer := 1;

  rows_affected integer := 0;
begin

  delete from cofk_sessions where session_code = session_for_deletion;

  get diagnostics rows_affected = row_count;
  if rows_affected != 1 then
    return failure;
  end if;

  return success;
end;

$$;


ALTER FUNCTION public.dbf_cofk_delete_session(session_for_deletion text) OWNER TO postgres;

--
-- Name: dbf_cofk_delete_user(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_delete_user(input_username character varying) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  statement varchar(100);
  rowcount integer;
  supervisor_role constant integer := -1;
  existing_user varchar(100);
  views_recd record;
begin
  -- Drop any views belonging to this user
  for views_recd in select viewname
                    from pg_views 
                    where schemaname = 'public'
                    and viewowner = input_username
  loop
    -- Double check that the view still exists
    -- as it may have been dropped by now, as a result of an earlier "cascade"
    select count(*) into rowcount
    from pg_views
    where schemaname = 'public'
    and viewowner = input_username
    and viewname = views_recd.viewname;

    if rowcount > 0 then
      statement = 'drop view ' || views_recd.viewname || ' cascade';
      execute statement;
    end if;
  end loop;

  delete from cofk_user_saved_query_selection	
  where
    query_id in ( select query_id from cofk_user_saved_queries
                  where username = input_username );

  delete from cofk_user_saved_queries
  where username = input_username;

  delete from cofk_sessions
  where
    username = input_username;

  delete from cofk_user_roles
  where
    username = input_username;

  delete from cofk_users
  where
    username = input_username;
  get diagnostics rowcount = row_count;

  -- Start a new block, so that ONLY the bit in the inner block 
  -- is rolled back if an error occurs.
  begin
    select usename
    into existing_user
    from pg_user
    where usename = input_username;

    if FOUND then
      statement = 'drop user ' || input_username ;
      execute statement;
    end if;

  exception
    when dependent_objects_still_exist then
      raise notice 'Cannot drop user % as dependent objects still exist', input_username;
      return 0;
  end;

  return rowcount;
end;

$$;


ALTER FUNCTION public.dbf_cofk_delete_user(input_username character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_get_column_label(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_get_column_label(column_name character varying) RETURNS character varying
    LANGUAGE plpgsql
    AS $$

begin
  return case 
    when column_name is null then ''
    when column_name = 'iperson_id' then 'Person ID'
    when column_name = 'person_id' then 'Person ID (for internal system use)'
    when column_name = 'iwork_id' then 'Work ID'
    when column_name = 'work_id' then 'Work ID (for internal system use)'
    when column_name = 'foaf_name' then 'Person or organisation name'
    when column_name = 'skos_altlabel' then 'Synonyms'
    when column_name = 'skos_hiddenlabel' then 'Other versions of name'
    when column_name = 'explicit' then 'Excipit'
    when column_name = 'date_of_work_std' then 'Date of Work (for ordering)'
    when column_name = 'date_of_work_std_gregorian' then 'Date of Work (for ordering, Gregorian)'
    when column_name = 'date_of_work_std_is_range' then 'Date of Work Is Range'
    when column_name = 'date_of_work_std_year' then 'Date of Work - Year (beginning of range or single date)'
    when column_name = 'date_of_work_std_month' then 'Date of Work - Month (beginning of range or single date)'
    when column_name = 'date_of_work_std_day' then 'Date of Work - Day (beginning of range or single date)'
    when column_name = 'date_of_work2_std_year' then 'Date of Work - Year (end of range)'
    when column_name = 'date_of_work2_std_month' then 'Date of Work - Month (end of range)'
    when column_name = 'date_of_work2_std_day' then 'Date of Work - Day (end of range)'
    when column_name = 'id_number_or_shelfmark' then 'ID number or shelfmark'
    else initcap( replace( column_name, '_', ' ' ))
  end;
end;

$$;


ALTER FUNCTION public.dbf_cofk_get_column_label(column_name character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_get_table_label(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_get_table_label(table_name character varying) RETURNS character varying
    LANGUAGE plpgsql
    AS $$

declare
  table_label varchar(100);
  underscores_to_remove smallint;
begin
  -- E.g. Convert 'cofk_union_comment' to 'Comment', 'cofk_lookup_document_type' to 'Document Type'.

  -- We'll assume that table names begin with two prefixes separated by underscores,
  -- e.g. cofk_selden_work or cofk_union_comment or cofk_lookup_document_type.
  -- Any tables that don't match this pattern will need special hard-coding adding here.

  table_label = table_name;

  underscores_to_remove = 2;
  while underscores_to_remove > 0 loop
    table_label = substr( table_label, strpos( table_label, '_' )+1 );
    underscores_to_remove = underscores_to_remove - 1;
  end loop;

  table_label = replace( table_label, '_', ' ' );
  table_label = initcap( table_label );

  return table_label;
end;

$$;


ALTER FUNCTION public.dbf_cofk_get_table_label(table_name character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_save_collect_tool_session_data(text, text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_save_collect_tool_session_data(input_username text, old_session text, new_session text) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  failure constant integer := 0;
  success constant integer := 1;

  rows_affected integer := 0;
begin
  if old_session is null or old_session = '' then

    insert into cofk_collect_tool_session (username, session_code)
    values (input_username, new_session );
  else
    update cofk_collect_tool_session 
    set 
      session_timestamp = current_timestamp
    where
      username = input_username
      and session_code = old_session;
  end if;

  get diagnostics rows_affected = row_count;
  if rows_affected != 1 then
    return failure;
  end if;

  delete from cofk_collect_tool_session where session_timestamp < current_date;

  return success;
end;

$$;


ALTER FUNCTION public.dbf_cofk_save_collect_tool_session_data(input_username text, old_session text, new_session text) OWNER TO postgres;

--
-- Name: dbf_cofk_save_session_data(text, text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_save_session_data(input_username text, old_session text, new_session text) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  failure constant integer := 0;
  success constant integer := 1;

  rows_affected integer := 0;
begin
  if old_session is null or old_session = '' then

    insert into cofk_sessions (username, session_code)
    values (input_username, new_session );
  else
    update cofk_sessions 
    set 
      session_timestamp = current_timestamp
    where
      username = input_username
      and session_code = old_session;
  end if;

  get diagnostics rows_affected = row_count;
  if rows_affected != 1 then
    return failure;
  end if;

  delete from cofk_sessions where session_timestamp < current_date;

  return success;
end;

$$;


ALTER FUNCTION public.dbf_cofk_save_session_data(input_username text, old_session text, new_session text) OWNER TO postgres;

--
-- Name: dbf_cofk_select_user(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_select_user(input_username text) RETURNS record
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  the_user cofk_users%rowtype;
begin
  select * from cofk_users 
  into the_user
  where username = input_username;

  the_user.pw = '';

  return the_user;
end;

$$;


ALTER FUNCTION public.dbf_cofk_select_user(input_username text) OWNER TO postgres;

--
-- Name: dbf_cofk_select_user_role_ids(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_select_user_role_ids(input_username character varying) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  role_string varchar(2000);
  role_row record;
begin
  role_string = '';
  for role_row in select r.role_id
                    from cofk_roles r, cofk_user_roles ur
                    where r.role_id = ur.role_id
                    and ur.username = input_username
                    order by r.role_id
  loop
    if role_string > '' then
      role_string := role_string || ', ';
    end if;

    role_string := role_string || role_row.role_id::varchar ;
  end loop;

  return role_string;
end;

$$;


ALTER FUNCTION public.dbf_cofk_select_user_role_ids(input_username character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_select_user_roles(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_select_user_roles(input_username character varying) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  role_string varchar(2000);
  role_row record;
begin
  role_string = '';
  for role_row in select r.role_code
                    from cofk_roles r, cofk_user_roles ur
                    where r.role_id = ur.role_id
                    and ur.username = input_username
                    order by r.role_code
  loop
    if role_string > '' then
      role_string := role_string || ', ';
    end if;

    role_string := role_string || '''' || role_row.role_code || '''';
  end loop;

  return role_string;
end;

$$;


ALTER FUNCTION public.dbf_cofk_select_user_roles(input_username character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_set_change_cols(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_set_change_cols() RETURNS trigger
    LANGUAGE plpgsql STRICT
    AS $$

begin
  new.change_user = user;
  new.change_timestamp = now();

  return new;
end;

$$;


ALTER FUNCTION public.dbf_cofk_set_change_cols() OWNER TO postgres;

--
-- Name: dbf_cofk_set_pw_by_super(character varying, character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_set_pw_by_super(md5_user_name character varying, token character varying, md5_pass character varying) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  existing_username varchar(100);
begin
  select dbf_cofk_decode_username( md5_user_name::text, token::text )
  into existing_username;

  if existing_username is null then
    raise exception 'No username found matching your selection.';
  else
    update cofk_users
    set pw = md5_pass
    where username = existing_username;
  end if;

  return existing_username;
end;

$$;


ALTER FUNCTION public.dbf_cofk_set_pw_by_super(md5_user_name character varying, token character varying, md5_pass character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_set_user_login_status(character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_set_user_login_status(input_username character varying, input_active integer) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

begin
  if input_active = 1 then
    update cofk_users
    set
      active = 1,
      failed_logins = 0
    where
      username = input_username;
  else
    update cofk_users
    set
      active = 0
    where
      username = input_username;

    delete from cofk_sessions
    where
      username = input_username;
  end if;

  return input_active;
end;

$$;


ALTER FUNCTION public.dbf_cofk_set_user_login_status(input_username character varying, input_active integer) OWNER TO postgres;

--
-- Name: dbf_cofk_set_user_roles(character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_set_user_roles(input_username character varying, input_roles character varying) RETURNS integer
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

declare
  supervisor_role constant integer := -1;
  statement varchar(500);
  one_role varchar(10);
  role_count integer;
  role_found integer;
  old_superpowers integer;
  new_superpowers integer;
begin
  select role_id into old_superpowers
  from cofk_user_roles
  where role_id = supervisor_role
  and username = input_username;
  old_superpowers = coalesce( old_superpowers, 0 );

  statement = 'delete from cofk_user_roles where username = ''' || input_username || '''';

  if trim( coalesce( input_roles, '' )) > '' then
    statement = statement || ' and role_id not in (' || input_roles || ')';
  end if;

  execute statement;

  -- Revoke supervisor permissions if supervisor role taken away
  select role_id into new_superpowers
  from cofk_user_roles
  where role_id = supervisor_role
  and username = input_username;
  new_superpowers = coalesce( new_superpowers, 0 );

  if old_superpowers = supervisor_role and new_superpowers != supervisor_role then
    statement = 'revoke super_role_cofk from ' || input_username;
    execute statement;
  end if;

  one_role = '?';
  role_count = 0;
  while one_role is not null and trim( one_role ) > '' loop
    role_count = role_count + 1;
    one_role = split_part( input_roles, ',', role_count );
    one_role = trim( one_role );
    if one_role = '' or one_role = '0' then
      exit;
    end if;

    role_found = 0;
    
    select role_id into role_found
    from cofk_user_roles
    where username = input_username
    and role_id = one_role::integer;

    if role_found is null or role_found = 0 then
      insert into cofk_user_roles (username, role_id)
      values (input_username, one_role::integer );

      -- Add supervisor permissions if supervisor role given
      if one_role::integer = supervisor_role and old_superpowers != supervisor_role then
        statement = 'grant super_role_cofk to ' || input_username;
        execute statement;
      end if;
    end if;
  end loop;

  role_count = role_count - 1;
  return role_count;
end;

$$;


ALTER FUNCTION public.dbf_cofk_set_user_roles(input_username character varying, input_roles character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_add_to_rels_decoded_string(character varying, character varying, date, date, integer, integer, integer, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_add_to_rels_decoded_string(input_table_name character varying, input_id_value character varying, input_start_date date, input_end_date date, html_output integer, expand_details integer, curr_item integer, input_string text) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  decode text;
  lookup_string text not null default '';
  newline constant varchar(1) default E'\n';
  start_date varchar(4);
  end_date varchar(4);
  list_item_marker varchar(6) not null := dbf_cofk_union_get_constant_value( 'list_item_marker' );
begin

  lookup_string = coalesce( input_string, '' );

  if lookup_string > '' then
    if html_output = 1 then
      if curr_item = 2 then -- put list-item markers round the first item in the list
        lookup_string := '<li>' || lookup_string || '</li>';
      end if;
      lookup_string := lookup_string || '<li>';
    else
      lookup_string := lookup_string || list_item_marker;
    end if;
  end if;

  decode = dbf_cofk_union_decode( input_table_name, input_id_value, 0,  -- don't suppress links
                                  expand_details );  -- the 'expand details' flag is only relevant to selected tables

  decode = coalesce( decode, '' );

  if input_start_date is not null and input_end_date is not null then
    start_date = to_char( input_start_date, 'YYYY' );
    end_date = to_char( input_end_date, 'YYYY' );
    if start_date = end_date then
      decode = start_date || ': ' || decode;
    else
      decode = start_date || '-' || end_date || ': ' || decode;
    end if;

  elsif input_start_date is not null then
    start_date = to_char( input_start_date, 'YYYY' );
    decode = 'From ' || start_date || ': ' || decode;

  elsif input_end_date is not null then
    end_date = to_char( input_end_date, 'YYYY' );
    decode = 'To ' || end_date || ': ' || decode;
  end if;

  lookup_string := lookup_string || decode;

  if html_output = 1 and curr_item > 1 then
    lookup_string := lookup_string || '</li>';
  end if;

  return lookup_string;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_add_to_rels_decoded_string(input_table_name character varying, input_id_value character varying, input_start_date date, input_end_date date, html_output integer, expand_details integer, curr_item integer, input_string text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_audit_any(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_audit_any() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
declare
  statement varchar(500);
begin

  if TG_RELNAME = 'cofk_union_comment' then

    -- cofk_union_comment 1. comment_id
    if TG_OP = 'DELETE' then 
      perform dbf_cofk_union_audit_literal_delete( 'cofk_union_comment', 
        old.comment_id::text, 
        old.comment_id, 
        old.comment, 
        'comment_id', 
        old.comment_id::text ); 
    end if;

    -- cofk_union_comment 2. comment
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_comment', 
        new.comment_id::text, 
        new.comment_id, 
        new.comment, 
        'comment', 
        new.comment::text, 
        old.comment::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_comment', 
        new.comment_id::text, 
        new.comment_id, 
        new.comment, 
        'comment', 
        new.comment::text ); 
    end if;

  end if; -- End of cofk_union_comment

-------------------

  if TG_RELNAME = 'cofk_union_event' then

  end if; -- End of cofk_union_event

-------------------

  if TG_RELNAME = 'cofk_union_image' then

    -- cofk_union_image 1. image_id
    if TG_OP = 'DELETE' then 
      perform dbf_cofk_union_audit_literal_delete( 'cofk_union_image', 
        old.image_id::text, 
        old.image_id, 
        old.image_filename, 
        'image_id', 
        old.image_id::text ); 
    end if;

    -- cofk_union_image 2. image_filename
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'image_filename', 
        new.image_filename::text, 
        old.image_filename::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'image_filename', 
        new.image_filename::text ); 
    end if;

    -- cofk_union_image 3. thumbnail
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'thumbnail', 
        new.thumbnail::text, 
        old.thumbnail::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'thumbnail', 
        new.thumbnail::text ); 
    end if;

    -- cofk_union_image 4. can_be_displayed
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'can_be_displayed', 
        new.can_be_displayed::text, 
        old.can_be_displayed::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'can_be_displayed', 
        new.can_be_displayed::text ); 
    end if;

    -- cofk_union_image 5. display_order
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'display_order', 
        new.display_order::text, 
        old.display_order::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'display_order', 
        new.display_order::text ); 
    end if;

    -- cofk_union_image 6. licence_details
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'licence_details', 
        new.licence_details::text, 
        old.licence_details::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'licence_details', 
        new.licence_details::text ); 
    end if;

    -- cofk_union_image 7. licence_url
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'licence_url', 
        new.licence_url::text, 
        old.licence_url::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'licence_url', 
        new.licence_url::text ); 
    end if;

    -- cofk_union_image 8. credits
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'credits', 
        new.credits::text, 
        old.credits::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_image', 
        new.image_id::text, 
        new.image_id, 
        new.image_filename, 
        'credits', 
        new.credits::text ); 
    end if;

  end if; -- End of cofk_union_image

-------------------

  if TG_RELNAME = 'cofk_union_institution' then

    -- cofk_union_institution 1. institution_id
    if TG_OP = 'DELETE' then 
      perform dbf_cofk_union_audit_literal_delete( 'cofk_union_institution', 
        old.institution_id::text, 
        old.institution_id, 
        old.institution_name ||  case when old.institution_city > '' then ', ' || old.institution_city else '' end || case when old.institution_country > '' then ',' || old.institution_country else '' end, 
        'institution_id', 
        old.institution_id::text ); 
    end if;

    -- cofk_union_institution 2. institution_name
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_institution', 
        new.institution_id::text, 
        new.institution_id, 
        new.institution_name ||  case when new.institution_city > '' then ', ' || new.institution_city else '' end || case when new.institution_country > '' then ',' || new.institution_country else '' end, 
        'institution_name', 
        new.institution_name::text, 
        old.institution_name::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_institution', 
        new.institution_id::text, 
        new.institution_id, 
        new.institution_name ||  case when new.institution_city > '' then ', ' || new.institution_city else '' end || case when new.institution_country > '' then ',' || new.institution_country else '' end, 
        'institution_name', 
        new.institution_name::text ); 
    end if;

    -- cofk_union_institution 3. institution_synonyms
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_institution', 
        new.institution_id::text, 
        new.institution_id, 
        new.institution_name ||  case when new.institution_city > '' then ', ' || new.institution_city else '' end || case when new.institution_country > '' then ',' || new.institution_country else '' end, 
        'institution_synonyms', 
        new.institution_synonyms::text, 
        old.institution_synonyms::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_institution', 
        new.institution_id::text, 
        new.institution_id, 
        new.institution_name ||  case when new.institution_city > '' then ', ' || new.institution_city else '' end || case when new.institution_country > '' then ',' || new.institution_country else '' end, 
        'institution_synonyms', 
        new.institution_synonyms::text ); 
    end if;

    -- cofk_union_institution 4. institution_city
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_institution', 
        new.institution_id::text, 
        new.institution_id, 
        new.institution_name ||  case when new.institution_city > '' then ', ' || new.institution_city else '' end || case when new.institution_country > '' then ',' || new.institution_country else '' end, 
        'institution_city', 
        new.institution_city::text, 
        old.institution_city::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_institution', 
        new.institution_id::text, 
        new.institution_id, 
        new.institution_name ||  case when new.institution_city > '' then ', ' || new.institution_city else '' end || case when new.institution_country > '' then ',' || new.institution_country else '' end, 
        'institution_city', 
        new.institution_city::text ); 
    end if;

    -- cofk_union_institution 5. institution_city_synonyms
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_institution', 
        new.institution_id::text, 
        new.institution_id, 
        new.institution_name ||  case when new.institution_city > '' then ', ' || new.institution_city else '' end || case when new.institution_country > '' then ',' || new.institution_country else '' end, 
        'institution_city_synonyms', 
        new.institution_city_synonyms::text, 
        old.institution_city_synonyms::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_institution', 
        new.institution_id::text, 
        new.institution_id, 
        new.institution_name ||  case when new.institution_city > '' then ', ' || new.institution_city else '' end || case when new.institution_country > '' then ',' || new.institution_country else '' end, 
        'institution_city_synonyms', 
        new.institution_city_synonyms::text ); 
    end if;

    -- cofk_union_institution 6. institution_country
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_institution', 
        new.institution_id::text, 
        new.institution_id, 
        new.institution_name ||  case when new.institution_city > '' then ', ' || new.institution_city else '' end || case when new.institution_country > '' then ',' || new.institution_country else '' end, 
        'institution_country', 
        new.institution_country::text, 
        old.institution_country::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_institution', 
        new.institution_id::text, 
        new.institution_id, 
        new.institution_name ||  case when new.institution_city > '' then ', ' || new.institution_city else '' end || case when new.institution_country > '' then ',' || new.institution_country else '' end, 
        'institution_country', 
        new.institution_country::text ); 
    end if;

    -- cofk_union_institution 7. institution_country_synonyms
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_institution', 
        new.institution_id::text, 
        new.institution_id, 
        new.institution_name ||  case when new.institution_city > '' then ', ' || new.institution_city else '' end || case when new.institution_country > '' then ',' || new.institution_country else '' end, 
        'institution_country_synonyms', 
        new.institution_country_synonyms::text, 
        old.institution_country_synonyms::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_institution', 
        new.institution_id::text, 
        new.institution_id, 
        new.institution_name ||  case when new.institution_city > '' then ', ' || new.institution_city else '' end || case when new.institution_country > '' then ',' || new.institution_country else '' end, 
        'institution_country_synonyms', 
        new.institution_country_synonyms::text ); 
    end if;

  end if; -- End of cofk_union_institution

-------------------

  if TG_RELNAME = 'cofk_union_location' then

    -- cofk_union_location 1. location_id
    if TG_OP = 'DELETE' then 
      perform dbf_cofk_union_audit_literal_delete( 'cofk_union_location', 
        old.location_id::text, 
        old.location_id, 
        old.location_name, 
        'location_id', 
        old.location_id::text ); 
    end if;

    -- cofk_union_location 2. location_name
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'location_name', 
        new.location_name::text, 
        old.location_name::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'location_name', 
        new.location_name::text ); 
    end if;

    -- cofk_union_location 3. latitude
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'latitude', 
        new.latitude::text, 
        old.latitude::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'latitude', 
        new.latitude::text ); 
    end if;

    -- cofk_union_location 4. longitude
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'longitude', 
        new.longitude::text, 
        old.longitude::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'longitude', 
        new.longitude::text ); 
    end if;

    -- cofk_union_location 5. location_synonyms
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'location_synonyms', 
        new.location_synonyms::text, 
        old.location_synonyms::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'location_synonyms', 
        new.location_synonyms::text ); 
    end if;

    -- cofk_union_location 6. editors_notes
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'editors_notes', 
        new.editors_notes::text, 
        old.editors_notes::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'editors_notes', 
        new.editors_notes::text ); 
    end if;

    -- cofk_union_location: placename element 1
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_1_eg_room', 
        new.element_1_eg_room::text, 
        old.element_1_eg_room::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_1_eg_room', 
        new.element_1_eg_room::text ); 
    end if;

    -- cofk_union_location: placename element 2
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_2_eg_building', 
        new.element_2_eg_building::text, 
        old.element_2_eg_building::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_2_eg_building', 
        new.element_2_eg_building::text ); 
    end if;

    -- cofk_union_location: placename element 3
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_3_eg_parish', 
        new.element_3_eg_parish::text, 
        old.element_3_eg_parish::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_3_eg_parish', 
        new.element_3_eg_parish::text ); 
    end if;

    -- cofk_union_location: placename element 4
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_4_eg_city', 
        new.element_4_eg_city::text, 
        old.element_4_eg_city::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_4_eg_city', 
        new.element_4_eg_city::text ); 
    end if;

    -- cofk_union_location: placename element 5
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_5_eg_county', 
        new.element_5_eg_county::text, 
        old.element_5_eg_county::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_5_eg_county', 
        new.element_5_eg_county::text ); 
    end if;

    -- cofk_union_location: placename element 6
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_6_eg_country', 
        new.element_6_eg_country::text, 
        old.element_6_eg_country::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_6_eg_country', 
        new.element_6_eg_country::text ); 
    end if;

    -- cofk_union_location: placename element 7
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_7_eg_empire', 
        new.element_7_eg_empire::text, 
        old.element_7_eg_empire::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_location', 
        new.location_id::text, 
        new.location_id, 
        new.location_name, 
        'element_7_eg_empire', 
        new.element_7_eg_empire::text ); 
    end if;
  end if; -- End of cofk_union_location

-------------------

  if TG_RELNAME = 'cofk_union_manifestation' then

    -- cofk_union_manifestation 1. manifestation_id
    if TG_OP = 'DELETE' then 
      perform dbf_cofk_union_audit_literal_delete( 'cofk_union_manifestation', 
        old.manifestation_id, 
        null, 
        coalesce( old.id_number_or_shelfmark, '' ) || coalesce( old.printed_edition_details, '' ), 
        'manifestation_id', 
        old.manifestation_id::text ); 
    end if;

    -- cofk_union_manifestation 2. manifestation_type
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_type', 
        new.manifestation_type::text, 
        old.manifestation_type::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_type', 
        new.manifestation_type::text ); 
    end if;

    -- cofk_union_manifestation 3. id_number_or_shelfmark
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'id_number_or_shelfmark', 
        new.id_number_or_shelfmark::text, 
        old.id_number_or_shelfmark::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'id_number_or_shelfmark', 
        new.id_number_or_shelfmark::text ); 
    end if;

    -- cofk_union_manifestation 4. printed_edition_details
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'printed_edition_details', 
        new.printed_edition_details::text, 
        old.printed_edition_details::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'printed_edition_details', 
        new.printed_edition_details::text ); 
    end if;

    -- cofk_union_manifestation 5. paper_size
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'paper_size', 
        new.paper_size::text, 
        old.paper_size::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'paper_size', 
        new.paper_size::text ); 
    end if;

    -- cofk_union_manifestation 6. paper_type_or_watermark
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'paper_type_or_watermark', 
        new.paper_type_or_watermark::text, 
        old.paper_type_or_watermark::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'paper_type_or_watermark', 
        new.paper_type_or_watermark::text ); 
    end if;

    -- cofk_union_manifestation 7. number_of_pages_of_document
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'number_of_pages_of_document', 
        new.number_of_pages_of_document::text, 
        old.number_of_pages_of_document::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'number_of_pages_of_document', 
        new.number_of_pages_of_document::text ); 
    end if;

    -- cofk_union_manifestation 8. number_of_pages_of_text
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'number_of_pages_of_text', 
        new.number_of_pages_of_text::text, 
        old.number_of_pages_of_text::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'number_of_pages_of_text', 
        new.number_of_pages_of_text::text ); 
    end if;

    -- cofk_union_manifestation 9. seal
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'seal', 
        new.seal::text, 
        old.seal::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'seal', 
        new.seal::text ); 
    end if;

    -- cofk_union_manifestation 10. postage_marks
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'postage_marks', 
        new.postage_marks::text, 
        old.postage_marks::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'postage_marks', 
        new.postage_marks::text ); 
    end if;

    -- cofk_union_manifestation 11. endorsements
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'endorsements', 
        new.endorsements::text, 
        old.endorsements::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'endorsements', 
        new.endorsements::text ); 
    end if;

    -- cofk_union_manifestation 12. non_letter_enclosures
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'non_letter_enclosures', 
        new.non_letter_enclosures::text, 
        old.non_letter_enclosures::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'non_letter_enclosures', 
        new.non_letter_enclosures::text ); 
    end if;

    -- cofk_union_manifestation 13. manifestation_creation_calendar
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_calendar', 
        new.manifestation_creation_calendar::text, 
        old.manifestation_creation_calendar::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_calendar', 
        new.manifestation_creation_calendar::text ); 
    end if;

    -- cofk_union_manifestation 14. manifestation_creation_date
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date', 
        new.manifestation_creation_date::text, 
        old.manifestation_creation_date::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date', 
        new.manifestation_creation_date::text ); 
    end if;

    -- cofk_union_manifestation 15. manifestation_creation_date_gregorian
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_gregorian', 
        new.manifestation_creation_date_gregorian::text, 
        old.manifestation_creation_date_gregorian::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_gregorian', 
        new.manifestation_creation_date_gregorian::text ); 
    end if;

    -- cofk_union_manifestation 16. manifestation_creation_date_year
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_year', 
        new.manifestation_creation_date_year::text, 
        old.manifestation_creation_date_year::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_year', 
        new.manifestation_creation_date_year::text ); 
    end if;

    -- cofk_union_manifestation 17. manifestation_creation_date_month
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_month', 
        new.manifestation_creation_date_month::text, 
        old.manifestation_creation_date_month::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_month', 
        new.manifestation_creation_date_month::text ); 
    end if;

    -- cofk_union_manifestation 18. manifestation_creation_date_day
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_day', 
        new.manifestation_creation_date_day::text, 
        old.manifestation_creation_date_day::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_day', 
        new.manifestation_creation_date_day::text ); 
    end if;

    -- cofk_union_manifestation 19. manifestation_creation_date_inferred
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_inferred', 
        new.manifestation_creation_date_inferred::text, 
        old.manifestation_creation_date_inferred::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_inferred', 
        new.manifestation_creation_date_inferred::text ); 
    end if;

    -- cofk_union_manifestation 20. manifestation_creation_date_uncertain
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_uncertain', 
        new.manifestation_creation_date_uncertain::text, 
        old.manifestation_creation_date_uncertain::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_uncertain', 
        new.manifestation_creation_date_uncertain::text ); 
    end if;

    -- cofk_union_manifestation 21. manifestation_creation_date_approx
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_approx', 
        new.manifestation_creation_date_approx::text, 
        old.manifestation_creation_date_approx::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_creation_date_approx', 
        new.manifestation_creation_date_approx::text ); 
    end if;

    -- cofk_union_manifestation 22. manifestation_is_translation
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_is_translation', 
        new.manifestation_is_translation::text, 
        old.manifestation_is_translation::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_is_translation', 
        new.manifestation_is_translation::text ); 
    end if;

    -- cofk_union_manifestation 23. language_of_manifestation
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'language_of_manifestation', 
        new.language_of_manifestation::text, 
        old.language_of_manifestation::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'language_of_manifestation', 
        new.language_of_manifestation::text ); 
    end if;

    -- cofk_union_manifestation 24. address
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'address', 
        new.address::text, 
        old.address::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'address', 
        new.address::text ); 
    end if;

    -- cofk_union_manifestation 25. manifestation_incipit
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_incipit', 
        new.manifestation_incipit::text, 
        old.manifestation_incipit::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_incipit', 
        new.manifestation_incipit::text ); 
    end if;

    -- cofk_union_manifestation 26. manifestation_excipit
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_excipit', 
        new.manifestation_excipit::text, 
        old.manifestation_excipit::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_excipit', 
        new.manifestation_excipit::text ); 
    end if;

    -- cofk_union_manifestation 27. manifestation_ps
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_ps', 
        new.manifestation_ps::text, 
        old.manifestation_ps::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_manifestation', 
        new.manifestation_id, 
        null, 
        coalesce( new.id_number_or_shelfmark, '' ) || coalesce( new.printed_edition_details, '' ), 
        'manifestation_ps', 
        new.manifestation_ps::text ); 
    end if;

  end if; -- End of cofk_union_manifestation

-------------------

  if TG_RELNAME = 'cofk_union_person' then

    -- cofk_union_person 1. person_id
    if TG_OP = 'DELETE' then 
      perform dbf_cofk_union_audit_literal_delete( 'cofk_union_person', 
        old.person_id, 
        old.iperson_id, 
        old.foaf_name, 
        'person_id', 
        old.person_id::text ); 
    end if;

    -- cofk_union_person 2. foaf_name
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'foaf_name', 
        new.foaf_name::text, 
        old.foaf_name::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'foaf_name', 
        new.foaf_name::text ); 
    end if;

    -- cofk_union_person 3. skos_altlabel
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'skos_altlabel', 
        new.skos_altlabel::text, 
        old.skos_altlabel::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'skos_altlabel', 
        new.skos_altlabel::text ); 
    end if;

    -- cofk_union_person 4. skos_hiddenlabel
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'skos_hiddenlabel', 
        new.skos_hiddenlabel::text, 
        old.skos_hiddenlabel::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'skos_hiddenlabel', 
        new.skos_hiddenlabel::text ); 
    end if;

    -- cofk_union_person 5. person_aliases
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'person_aliases', 
        new.person_aliases::text, 
        old.person_aliases::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'person_aliases', 
        new.person_aliases::text ); 
    end if;

    -- cofk_union_person 6. date_of_birth_year
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_year', 
        new.date_of_birth_year::text, 
        old.date_of_birth_year::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_year', 
        new.date_of_birth_year::text ); 
    end if;

    -- cofk_union_person 7. date_of_birth_month
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_month', 
        new.date_of_birth_month::text, 
        old.date_of_birth_month::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_month', 
        new.date_of_birth_month::text ); 
    end if;

    -- cofk_union_person 8. date_of_birth_day
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_day', 
        new.date_of_birth_day::text, 
        old.date_of_birth_day::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_day', 
        new.date_of_birth_day::text ); 
    end if;

    -- cofk_union_person 6x. date_of_birth2_year
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth2_year', 
        new.date_of_birth2_year::text, 
        old.date_of_birth2_year::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth2_year', 
        new.date_of_birth2_year::text ); 
    end if;

    -- cofk_union_person 7x. date_of_birth2_month
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth2_month', 
        new.date_of_birth2_month::text, 
        old.date_of_birth2_month::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth2_month', 
        new.date_of_birth2_month::text ); 
    end if;

    -- cofk_union_person 8x. date_of_birth2_day
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth2_day', 
        new.date_of_birth2_day::text, 
        old.date_of_birth2_day::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth2_day', 
        new.date_of_birth2_day::text ); 
    end if;

    -- cofk_union_person 8xx. date_of_birth_calendar
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_calendar', 
        new.date_of_birth_calendar::text, 
        old.date_of_birth_calendar::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_calendar', 
        new.date_of_birth_calendar::text ); 
    end if;

    -- cofk_union_person 8xxx. date_of_birth_is_range
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_is_range', 
        new.date_of_birth_is_range::text, 
        old.date_of_birth_is_range::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_is_range', 
        new.date_of_birth_is_range::text ); 
    end if;

    -- cofk_union_person 9. date_of_birth
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth', 
        new.date_of_birth::text, 
        old.date_of_birth::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth', 
        new.date_of_birth::text ); 
    end if;

    -- cofk_union_person 10. date_of_birth_inferred
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_inferred', 
        new.date_of_birth_inferred::text, 
        old.date_of_birth_inferred::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_inferred', 
        new.date_of_birth_inferred::text ); 
    end if;

    -- cofk_union_person 11. date_of_birth_uncertain
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_uncertain', 
        new.date_of_birth_uncertain::text, 
        old.date_of_birth_uncertain::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_uncertain', 
        new.date_of_birth_uncertain::text ); 
    end if;

    -- cofk_union_person 12. date_of_birth_approx
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_approx', 
        new.date_of_birth_approx::text, 
        old.date_of_birth_approx::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_birth_approx', 
        new.date_of_birth_approx::text ); 
    end if;

    -- cofk_union_person 13. date_of_death_year
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_year', 
        new.date_of_death_year::text, 
        old.date_of_death_year::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_year', 
        new.date_of_death_year::text ); 
    end if;

    -- cofk_union_person 14. date_of_death_month
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_month', 
        new.date_of_death_month::text, 
        old.date_of_death_month::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_month', 
        new.date_of_death_month::text ); 
    end if;

    -- cofk_union_person 15. date_of_death_day
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_day', 
        new.date_of_death_day::text, 
        old.date_of_death_day::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_day', 
        new.date_of_death_day::text ); 
    end if;

    -- cofk_union_person 13x. date_of_death2_year
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death2_year', 
        new.date_of_death2_year::text, 
        old.date_of_death2_year::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death2_year', 
        new.date_of_death2_year::text ); 
    end if;

    -- cofk_union_person 14x. date_of_death2_month
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death2_month', 
        new.date_of_death2_month::text, 
        old.date_of_death2_month::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death2_month', 
        new.date_of_death2_month::text ); 
    end if;

    -- cofk_union_person 15x. date_of_death2_day
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death2_day', 
        new.date_of_death2_day::text, 
        old.date_of_death2_day::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death2_day', 
        new.date_of_death2_day::text ); 
    end if;

    -- cofk_union_person 15xx. date_of_death_calendar
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_calendar', 
        new.date_of_death_calendar::text, 
        old.date_of_death_calendar::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_calendar', 
        new.date_of_death_calendar::text ); 
    end if;

    -- cofk_union_person 15xxx. date_of_death_is_range
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_is_range', 
        new.date_of_death_is_range::text, 
        old.date_of_death_is_range::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_is_range', 
        new.date_of_death_is_range::text ); 
    end if;


    -- cofk_union_person 16. date_of_death
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death', 
        new.date_of_death::text, 
        old.date_of_death::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death', 
        new.date_of_death::text ); 
    end if;

    -- cofk_union_person 17. date_of_death_inferred
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_inferred', 
        new.date_of_death_inferred::text, 
        old.date_of_death_inferred::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_inferred', 
        new.date_of_death_inferred::text ); 
    end if;

    -- cofk_union_person 18. date_of_death_uncertain
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_uncertain', 
        new.date_of_death_uncertain::text, 
        old.date_of_death_uncertain::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_uncertain', 
        new.date_of_death_uncertain::text ); 
    end if;

    -- cofk_union_person 19. date_of_death_approx
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_approx', 
        new.date_of_death_approx::text, 
        old.date_of_death_approx::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'date_of_death_approx', 
        new.date_of_death_approx::text ); 
    end if;

    -- cofk_union_person 20. iperson_id
    if TG_OP = 'DELETE' then 
      perform dbf_cofk_union_audit_literal_delete( 'cofk_union_person', 
        old.person_id, 
        old.iperson_id, 
        old.foaf_name, 
        'iperson_id', 
        old.iperson_id::text ); 
    end if;

    -- cofk_union_person 21. editors_notes
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name,  -- decode
        'editors_notes', 
        new.editors_notes::text, 
        old.editors_notes::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name,  -- decode
        'editors_notes', 
        new.editors_notes::text ); 
    end if;

    -- cofk_union_person 22. further_reading
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name,  -- decode
        'further_reading', 
        new.further_reading::text, 
        old.further_reading::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name,  -- decode
        'further_reading', 
        new.further_reading::text ); 
    end if;

    -- cofk_union_person 23. flourished_year
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished_year', 
        new.flourished_year::text, 
        old.flourished_year::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished_year', 
        new.flourished_year::text ); 
    end if;

    -- cofk_union_person 24. flourished_month
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished_month', 
        new.flourished_month::text, 
        old.flourished_month::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished_month', 
        new.flourished_month::text ); 
    end if;

    -- cofk_union_person 25. flourished_day
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished_day', 
        new.flourished_day::text, 
        old.flourished_day::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished_day', 
        new.flourished_day::text ); 
    end if;

    -- cofk_union_person 26. flourished2_year
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished2_year', 
        new.flourished2_year::text, 
        old.flourished2_year::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished2_year', 
        new.flourished2_year::text ); 
    end if;

    -- cofk_union_person 27. flourished2_month
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished2_month', 
        new.flourished2_month::text, 
        old.flourished2_month::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished2_month', 
        new.flourished2_month::text ); 
    end if;

    -- cofk_union_person 28. flourished2_day
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished2_day', 
        new.flourished2_day::text, 
        old.flourished2_day::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished2_day', 
        new.flourished2_day::text ); 
    end if;

    -- cofk_union_person 29. flourished_calendar
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished_calendar', 
        new.flourished_calendar::text, 
        old.flourished_calendar::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished_calendar', 
        new.flourished_calendar::text ); 
    end if;

    -- cofk_union_person 30. flourished_is_range
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished_is_range', 
        new.flourished_is_range::text, 
        old.flourished_is_range::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'flourished_is_range', 
        new.flourished_is_range::text ); 
    end if;

    -- cofk_union_person: organisation type
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'organisation_type', 
        coalesce( ( select org_type_desc from cofk_union_org_type where org_type_id = new.organisation_type ), 
                  '' ), 
        coalesce( ( select org_type_desc from cofk_union_org_type where org_type_id = old.organisation_type ), 
                  '' ) ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_person', 
        new.person_id, 
        new.iperson_id, 
        new.foaf_name, 
        'organisation_type', 
        coalesce( ( select org_type_desc from cofk_union_org_type where org_type_id = new.organisation_type), 
                  '' ) ); 
    end if;


  end if; -- End of cofk_union_person

-------------------

  if TG_RELNAME = 'cofk_union_publication' then

    -- cofk_union_publication 1. publication_id
    if TG_OP = 'DELETE' then 
      perform dbf_cofk_union_audit_literal_delete( 'cofk_union_publication', 
        old.publication_id::text, 
        old.publication_id, 
        old.publication_details, 
        'publication_id', 
        old.publication_id::text ); 
    end if;

    -- cofk_union_publication 2. publication_details
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_publication', 
        new.publication_id::text, 
        new.publication_id, 
        new.publication_details, 
        'publication_details', 
        new.publication_details::text, 
        old.publication_details::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_publication', 
        new.publication_id::text, 
        new.publication_id, 
        new.publication_details, 
        'publication_details', 
        new.publication_details::text ); 
    end if;

    -- cofk_union_publication 2. abbreviation
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_publication', 
        new.publication_id::text, 
        new.publication_id, 
        new.publication_details, 
        'abbrev', 
        new.abbrev::text, 
        old.abbrev::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_publication', 
        new.publication_id::text, 
        new.publication_id, 
        new.publication_details, 
        'abbrev', 
        new.abbrev::text ); 
    end if;

  end if; -- End of cofk_union_publication

-------------------

  if TG_RELNAME = 'cofk_union_relationship' then

    -- cofk_union_relationship 1. relationship_id
    if TG_OP = 'UPDATE' then 
      ------------------------------------
      -- Change of ID value on either side
      ------------------------------------
      if new.left_id_value != old.left_id_value or new.right_id_value != old.right_id_value then
        perform dbf_cofk_union_audit_relationship_update(
          new.left_table_name,
          new.left_id_value,
          (select dbf_cofk_union_decode( new.left_table_name, new.left_id_value )),
          old.left_id_value,
          (select dbf_cofk_union_decode( old.left_table_name, old.left_id_value )),
          new.relationship_type,
          (select desc_left_to_right from cofk_union_relationship_type 
          where relationship_code = new.relationship_type),
          (select desc_right_to_left from cofk_union_relationship_type 
          where relationship_code = new.relationship_type),
          new.right_table_name,
          new.right_id_value,
          (select dbf_cofk_union_decode( new.right_table_name, new.right_id_value )),
          old.right_id_value,
          (select dbf_cofk_union_decode( old.right_table_name, old.right_id_value ))
        ); 
      end if;

      if new.right_id_value != old.right_id_value then
        perform dbf_cofk_union_audit_literal_update( new.left_table_name, 
          new.left_id_value, 
          new.relationship_id,  -- integer ID here holds relationship ID 
          (select dbf_cofk_union_decode( new.left_table_name, new.left_id_value )),
          (select 'Relationship: ' || desc_left_to_right from cofk_union_relationship_type 
           where relationship_code = new.relationship_type),
          (select dbf_cofk_union_decode( new.right_table_name, new.right_id_value )),
          (select dbf_cofk_union_decode( old.right_table_name, old.right_id_value ))
        ); 
        perform dbf_cofk_union_audit_literal_insert( new.right_table_name, 
          new.right_id_value, 
          new.relationship_id,  -- integer ID here holds relationship ID 
          (select dbf_cofk_union_decode( new.right_table_name, new.right_id_value )),
          (select 'Relationship: ' || desc_right_to_left from cofk_union_relationship_type 
           where relationship_code = new.relationship_type),
          (select dbf_cofk_union_decode( new.left_table_name, new.left_id_value ))
        ); 
        perform dbf_cofk_union_audit_literal_delete( old.right_table_name, 
          old.right_id_value, 
          old.relationship_id,  -- integer ID here holds relationship ID 
          (select dbf_cofk_union_decode( old.right_table_name, old.right_id_value )),
          (select 'Relationship: ' || desc_right_to_left from cofk_union_relationship_type 
           where relationship_code = old.relationship_type),
          (select dbf_cofk_union_decode( old.left_table_name, old.left_id_value ))
        ); 
      end if;

      if new.left_id_value != old.left_id_value then
        perform dbf_cofk_union_audit_literal_update( new.right_table_name, 
          new.right_id_value, 
          new.relationship_id,  -- integer ID here holds relationship ID 
          (select dbf_cofk_union_decode( new.right_table_name, new.right_id_value )),
          (select 'Relationship: ' || desc_right_to_left from cofk_union_relationship_type 
           where relationship_code = new.relationship_type),
          (select dbf_cofk_union_decode( new.left_table_name, new.left_id_value )),
          (select dbf_cofk_union_decode( old.left_table_name, old.left_id_value ))
        ); 
        perform dbf_cofk_union_audit_literal_insert( new.left_table_name, 
          new.left_id_value, 
          new.relationship_id,  -- integer ID here holds relationship ID 
          (select dbf_cofk_union_decode( new.left_table_name, new.left_id_value )),
          (select 'Relationship: ' || desc_left_to_right from cofk_union_relationship_type 
           where relationship_code = new.relationship_type),
          (select dbf_cofk_union_decode( new.right_table_name, new.right_id_value ))
        ); 
        perform dbf_cofk_union_audit_literal_delete( old.left_table_name, 
          old.left_id_value, 
          old.relationship_id,  -- integer ID here holds relationship ID 
          (select dbf_cofk_union_decode( old.left_table_name, old.left_id_value )),
          (select 'Relationship: ' || desc_left_to_right from cofk_union_relationship_type 
           where relationship_code = old.relationship_type),
          (select dbf_cofk_union_decode( old.right_table_name, old.right_id_value ))
        ); 
      end if;

      ------------------------------------
      -- Change of relationship start date
      ------------------------------------
      if coalesce( new.relationship_valid_from, '9999-12-31'::date )
      != coalesce( old.relationship_valid_from, '9999-12-31'::date ) then

        perform dbf_cofk_union_audit_literal_update( 

          'cofk_union_relationship',   -- table_name

          new.left_id_value || ' ' || new.right_id_value, -- key_value_text 

          new.relationship_id,  -- key_value_integer

          (select dbf_cofk_union_decode( new.left_table_name, new.left_id_value ))
            || ' ' 
            || (select desc_left_to_right from cofk_union_relationship_type
                where relationship_code = new.relationship_type) 
            || ' ' 
            || (select dbf_cofk_union_decode( new.right_table_name, new.right_id_value )), -- key_decode

          'relationship_valid_from',   -- column_name
          new.relationship_valid_from::date::text, -- new_column_value
          old.relationship_valid_from::date::text  -- old_column_value
        ); 
      end if;

      ------------------------------------
      -- Change of relationship end date
      ------------------------------------
      if coalesce( new.relationship_valid_till, '9999-12-31'::date )
      != coalesce( old.relationship_valid_till, '9999-12-31'::date ) then

        perform dbf_cofk_union_audit_literal_update( 

          'cofk_union_relationship',   -- table_name

          new.left_id_value || ' ' || new.right_id_value, -- key_value_text 

          new.relationship_id,  -- key_value_integer

          (select dbf_cofk_union_decode( new.left_table_name, new.left_id_value ))
            || ' ' 
            || (select desc_left_to_right from cofk_union_relationship_type
                where relationship_code = new.relationship_type) 
            || ' ' 
            || (select dbf_cofk_union_decode( new.right_table_name, new.right_id_value )), -- key_decode

          'relationship_valid_till',    -- column_name
          new.relationship_valid_till::date::text, -- new_column_value
          old.relationship_valid_till::date::text  -- old_column_value
        ); 
      end if;
    end if;  -- end of 'relationship update' section

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_relationship_insert(
        new.left_table_name,
        new.left_id_value,
        (select dbf_cofk_union_decode( new.left_table_name, new.left_id_value )),
        new.relationship_type,
        (select desc_left_to_right from cofk_union_relationship_type 
        where relationship_code = new.relationship_type),
        (select desc_right_to_left from cofk_union_relationship_type 
        where relationship_code = new.relationship_type),
        new.right_table_name,
        new.right_id_value,
        (select dbf_cofk_union_decode( new.right_table_name, new.right_id_value ))
      ); 

      perform dbf_cofk_union_audit_literal_insert( new.left_table_name, 
        new.left_id_value, 
        new.relationship_id,  -- integer ID here holds relationship ID 
        (select dbf_cofk_union_decode( new.left_table_name, new.left_id_value )),
        (select 'Relationship: ' || desc_left_to_right from cofk_union_relationship_type 
         where relationship_code = new.relationship_type),
        (select dbf_cofk_union_decode( new.right_table_name, new.right_id_value ))
      ); 
      perform dbf_cofk_union_audit_literal_insert( new.right_table_name, 
        new.right_id_value, 
        new.relationship_id,  -- integer ID here holds relationship ID 
        (select dbf_cofk_union_decode( new.right_table_name, new.right_id_value )),
        (select 'Relationship: ' || desc_right_to_left from cofk_union_relationship_type 
         where relationship_code = new.relationship_type),
        (select dbf_cofk_union_decode( new.left_table_name, new.left_id_value ))
      ); 

      ------------------------------
      -- New relationship start date
      ------------------------------
      perform dbf_cofk_union_audit_literal_insert( 

        'cofk_union_relationship',   -- table_name

        new.left_id_value || ' ' || new.right_id_value, -- key_value_text 

        new.relationship_id,  -- key_value_integer

        (select dbf_cofk_union_decode( new.left_table_name, new.left_id_value ))
          || ' ' 
          || (select desc_left_to_right from cofk_union_relationship_type
              where relationship_code = new.relationship_type) 
          || ' ' 
          || (select dbf_cofk_union_decode( new.right_table_name, new.right_id_value )), -- key_decode

        'relationship_valid_from',    -- column_name
        new.relationship_valid_from::date::text   -- new_column_value
      ); 

      ------------------------------
      -- New relationship end date
      ------------------------------
      perform dbf_cofk_union_audit_literal_insert( 

        'cofk_union_relationship',   -- table_name

        new.left_id_value || ' ' || new.right_id_value, -- key_value_text 

        new.relationship_id,  -- key_value_integer

        (select dbf_cofk_union_decode( new.left_table_name, new.left_id_value ))
          || ' ' 
          || (select desc_left_to_right from cofk_union_relationship_type
              where relationship_code = new.relationship_type) 
          || ' ' 
          || (select dbf_cofk_union_decode( new.right_table_name, new.right_id_value )), -- key_decode

        'relationship_valid_till',    -- column_name
        new.relationship_valid_till::date::text   -- new_column_value
      ); 
    end if;

    if TG_OP = 'DELETE' then 
      perform dbf_cofk_union_audit_relationship_delete(
        old.left_table_name,
        old.left_id_value,
        (select dbf_cofk_union_decode( old.left_table_name, old.left_id_value )),
        old.relationship_type,
        (select desc_left_to_right from cofk_union_relationship_type 
        where relationship_code = old.relationship_type),
        (select desc_right_to_left from cofk_union_relationship_type 
        where relationship_code = old.relationship_type),
        old.right_table_name,
        old.right_id_value,
        (select dbf_cofk_union_decode( old.right_table_name, old.right_id_value ))
      ); 

      perform dbf_cofk_union_audit_literal_delete( old.left_table_name, 
        old.left_id_value, 
        old.relationship_id,  -- integer ID here holds relationship ID 
        (select dbf_cofk_union_decode( old.left_table_name, old.left_id_value )),
        (select 'Relationship: ' || desc_left_to_right from cofk_union_relationship_type 
         where relationship_code = old.relationship_type),
        (select dbf_cofk_union_decode( old.right_table_name, old.right_id_value ))
      ); 
      perform dbf_cofk_union_audit_literal_delete( old.right_table_name, 
        old.right_id_value, 
        old.relationship_id,  -- integer ID here holds relationship ID 
        (select dbf_cofk_union_decode( old.right_table_name, old.right_id_value )),
        (select 'Relationship: ' || desc_right_to_left from cofk_union_relationship_type 
         where relationship_code = old.relationship_type),
        (select dbf_cofk_union_decode( old.left_table_name, old.left_id_value ))
      ); 
    end if;

    -- cofk_union_relationship 2. left_table_name
    -- cofk_union_relationship 3. left_id_value
    -- cofk_union_relationship 4. relationship_type
    -- cofk_union_relationship 5. right_table_name
    -- cofk_union_relationship 6. right_id_value
    -- cofk_union_relationship 7. relationship_valid_from
    -- cofk_union_relationship 8. relationship_valid_till
  end if; -- End of cofk_union_relationship

-------------------

  if TG_RELNAME = 'cofk_union_relationship_type' then

    -- cofk_union_relationship_type 1. relationship_code
    if TG_OP = 'DELETE' then 
      perform dbf_cofk_union_audit_literal_delete( 'cofk_union_relationship_type', 
        old.relationship_code, 
        null, 
        old.desc_left_to_right, 
        'relationship_code', 
        old.relationship_code::text ); 
    end if;

    -- cofk_union_relationship_type 2. desc_left_to_right
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_relationship_type', 
        new.relationship_code, 
        null, 
        new.desc_left_to_right, 
        'desc_left_to_right', 
        new.desc_left_to_right::text, 
        old.desc_left_to_right::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_relationship_type', 
        new.relationship_code, 
        null, 
        new.desc_left_to_right, 
        'desc_left_to_right', 
        new.desc_left_to_right::text ); 
    end if;

    -- cofk_union_relationship_type 3. desc_right_to_left
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_relationship_type', 
        new.relationship_code, 
        null, 
        new.desc_left_to_right, 
        'desc_right_to_left', 
        new.desc_right_to_left::text, 
        old.desc_right_to_left::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_relationship_type', 
        new.relationship_code, 
        null, 
        new.desc_left_to_right, 
        'desc_right_to_left', 
        new.desc_right_to_left::text ); 
    end if;

  end if; -- End of cofk_union_relationship_type

-------------------

  if TG_RELNAME = 'cofk_union_resource' then

    -- cofk_union_resource 1. resource_id
    if TG_OP = 'DELETE' then 
      perform dbf_cofk_union_audit_literal_delete( 'cofk_union_resource', 
        old.resource_id::text, 
        old.resource_id, 
        old.resource_name, 
        'resource_id', 
        old.resource_id::text ); 
    end if;

    -- cofk_union_resource 2. resource_name
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_resource', 
        new.resource_id::text, 
        new.resource_id, 
        new.resource_name, 
        'resource_name', 
        new.resource_name::text, 
        old.resource_name::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_resource', 
        new.resource_id::text, 
        new.resource_id, 
        new.resource_name, 
        'resource_name', 
        new.resource_name::text ); 
    end if;

    -- cofk_union_resource 3. resource_details
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_resource', 
        new.resource_id::text, 
        new.resource_id, 
        new.resource_name, 
        'resource_details', 
        new.resource_details::text, 
        old.resource_details::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_resource', 
        new.resource_id::text, 
        new.resource_id, 
        new.resource_name, 
        'resource_details', 
        new.resource_details::text ); 
    end if;

    -- cofk_union_resource 4. resource_url
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_resource', 
        new.resource_id::text, 
        new.resource_id, 
        new.resource_name, 
        'resource_url', 
        new.resource_url::text, 
        old.resource_url::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_resource', 
        new.resource_id::text, 
        new.resource_id, 
        new.resource_name, 
        'resource_url', 
        new.resource_url::text ); 
    end if;

  end if; -- End of cofk_union_resource

-------------------

  if TG_RELNAME = 'cofk_union_work' then

    -- cofk_union_work 1. work_id
    if TG_OP = 'DELETE' then 
      perform dbf_cofk_union_audit_literal_delete( 'cofk_union_work', 
        old.work_id, 
        old.iwork_id, 
        old.description, 
        'work_id', 
        old.work_id::text ); 
    end if;

    -- cofk_union_work 2. description
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'description', 
        new.description::text, 
        old.description::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'description', 
        new.description::text ); 
    end if;

    -- cofk_union_work 3. date_of_work_as_marked
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_as_marked', 
        new.date_of_work_as_marked::text, 
        old.date_of_work_as_marked::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_as_marked', 
        new.date_of_work_as_marked::text ); 
    end if;

    -- cofk_union_work 4. original_calendar
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'original_calendar', 
        new.original_calendar::text, 
        old.original_calendar::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'original_calendar', 
        new.original_calendar::text ); 
    end if;

    -- cofk_union_work 5. date_of_work_std
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_std', 
        new.date_of_work_std::text, 
        old.date_of_work_std::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_std', 
        new.date_of_work_std::text ); 
    end if;

    -- cofk_union_work 6. date_of_work_std_gregorian
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_std_gregorian', 
        new.date_of_work_std_gregorian::text, 
        old.date_of_work_std_gregorian::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_std_gregorian', 
        new.date_of_work_std_gregorian::text ); 
    end if;

    -- cofk_union_work 7. date_of_work_std_year
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_std_year', 
        new.date_of_work_std_year::text, 
        old.date_of_work_std_year::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_std_year', 
        new.date_of_work_std_year::text ); 
    end if;

    -- cofk_union_work 8. date_of_work_std_month
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_std_month', 
        new.date_of_work_std_month::text, 
        old.date_of_work_std_month::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_std_month', 
        new.date_of_work_std_month::text ); 
    end if;

    -- cofk_union_work 9. date_of_work_std_day
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_std_day', 
        new.date_of_work_std_day::text, 
        old.date_of_work_std_day::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_std_day', 
        new.date_of_work_std_day::text ); 
    end if;

    -- cofk_union_work 10. date_of_work2_std_year
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work2_std_year', 
        new.date_of_work2_std_year::text, 
        old.date_of_work2_std_year::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work2_std_year', 
        new.date_of_work2_std_year::text ); 
    end if;

    -- cofk_union_work 11. date_of_work2_std_month
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work2_std_month', 
        new.date_of_work2_std_month::text, 
        old.date_of_work2_std_month::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work2_std_month', 
        new.date_of_work2_std_month::text ); 
    end if;

    -- cofk_union_work 12. date_of_work2_std_day
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work2_std_day', 
        new.date_of_work2_std_day::text, 
        old.date_of_work2_std_day::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work2_std_day', 
        new.date_of_work2_std_day::text ); 
    end if;

    -- cofk_union_work 13. date_of_work_std_is_range
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_std_is_range', 
        new.date_of_work_std_is_range::text, 
        old.date_of_work_std_is_range::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_std_is_range', 
        new.date_of_work_std_is_range::text ); 
    end if;

    -- cofk_union_work 14. date_of_work_inferred
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_inferred', 
        new.date_of_work_inferred::text, 
        old.date_of_work_inferred::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_inferred', 
        new.date_of_work_inferred::text ); 
    end if;

    -- cofk_union_work 15. date_of_work_uncertain
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_uncertain', 
        new.date_of_work_uncertain::text, 
        old.date_of_work_uncertain::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_uncertain', 
        new.date_of_work_uncertain::text ); 
    end if;

    -- cofk_union_work 16. date_of_work_approx
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_approx', 
        new.date_of_work_approx::text, 
        old.date_of_work_approx::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'date_of_work_approx', 
        new.date_of_work_approx::text ); 
    end if;

    -- cofk_union_work 17. authors_as_marked
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'authors_as_marked', 
        new.authors_as_marked::text, 
        old.authors_as_marked::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'authors_as_marked', 
        new.authors_as_marked::text ); 
    end if;

    -- cofk_union_work 18. addressees_as_marked
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'addressees_as_marked', 
        new.addressees_as_marked::text, 
        old.addressees_as_marked::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'addressees_as_marked', 
        new.addressees_as_marked::text ); 
    end if;

    -- cofk_union_work 19. authors_inferred
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'authors_inferred', 
        new.authors_inferred::text, 
        old.authors_inferred::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'authors_inferred', 
        new.authors_inferred::text ); 
    end if;

    -- cofk_union_work 20. authors_uncertain
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'authors_uncertain', 
        new.authors_uncertain::text, 
        old.authors_uncertain::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'authors_uncertain', 
        new.authors_uncertain::text ); 
    end if;

    -- cofk_union_work 21. addressees_inferred
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'addressees_inferred', 
        new.addressees_inferred::text, 
        old.addressees_inferred::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'addressees_inferred', 
        new.addressees_inferred::text ); 
    end if;

    -- cofk_union_work 22. addressees_uncertain
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'addressees_uncertain', 
        new.addressees_uncertain::text, 
        old.addressees_uncertain::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'addressees_uncertain', 
        new.addressees_uncertain::text ); 
    end if;

    -- cofk_union_work 23. destination_as_marked
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'destination_as_marked', 
        new.destination_as_marked::text, 
        old.destination_as_marked::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'destination_as_marked', 
        new.destination_as_marked::text ); 
    end if;

    -- cofk_union_work 24. origin_as_marked
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'origin_as_marked', 
        new.origin_as_marked::text, 
        old.origin_as_marked::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'origin_as_marked', 
        new.origin_as_marked::text ); 
    end if;

    -- cofk_union_work 25. destination_inferred
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'destination_inferred', 
        new.destination_inferred::text, 
        old.destination_inferred::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'destination_inferred', 
        new.destination_inferred::text ); 
    end if;

    -- cofk_union_work 26. destination_uncertain
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'destination_uncertain', 
        new.destination_uncertain::text, 
        old.destination_uncertain::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'destination_uncertain', 
        new.destination_uncertain::text ); 
    end if;

    -- cofk_union_work 27. origin_inferred
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'origin_inferred', 
        new.origin_inferred::text, 
        old.origin_inferred::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'origin_inferred', 
        new.origin_inferred::text ); 
    end if;

    -- cofk_union_work 28. origin_uncertain
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'origin_uncertain', 
        new.origin_uncertain::text, 
        old.origin_uncertain::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'origin_uncertain', 
        new.origin_uncertain::text ); 
    end if;

    -- cofk_union_work 29. abstract
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'abstract', 
        new.abstract::text, 
        old.abstract::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'abstract', 
        new.abstract::text ); 
    end if;

    -- cofk_union_work 30. keywords
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'keywords', 
        new.keywords::text, 
        old.keywords::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'keywords', 
        new.keywords::text ); 
    end if;

    -- cofk_union_work 31. language_of_work
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'language_of_work', 
        new.language_of_work::text, 
        old.language_of_work::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'language_of_work', 
        new.language_of_work::text ); 
    end if;

    -- cofk_union_work 32. work_is_translation
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'work_is_translation', 
        new.work_is_translation::text, 
        old.work_is_translation::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'work_is_translation', 
        new.work_is_translation::text ); 
    end if;

    -- cofk_union_work 33. incipit
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'incipit', 
        new.incipit::text, 
        old.incipit::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'incipit', 
        new.incipit::text ); 
    end if;

    -- cofk_union_work 34. explicit
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'explicit', 
        new.explicit::text, 
        old.explicit::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'explicit', 
        new.explicit::text ); 
    end if;

    -- cofk_union_work 35. ps
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'ps', 
        new.ps::text, 
        old.ps::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'ps', 
        new.ps::text ); 
    end if;

    -- cofk_union_work 36. accession_code
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'accession_code', 
        new.accession_code::text, 
        old.accession_code::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'accession_code', 
        new.accession_code::text ); 
    end if;

    -- cofk_union_work 37. work_to_be_deleted
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'work_to_be_deleted', 
        new.work_to_be_deleted::text, 
        old.work_to_be_deleted::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'work_to_be_deleted', 
        new.work_to_be_deleted::text ); 
    end if;

    -- cofk_union_work 38. iwork_id
    if TG_OP = 'UPDATE' then 
      update cofk_union_queryable_work set change_timestamp = 'now'::timestamp 
      where iwork_id = new.iwork_id;
    end if;

    if TG_OP = 'DELETE' then 
      perform dbf_cofk_union_audit_literal_delete( 'cofk_union_work', 
        old.work_id, 
        old.iwork_id, 
        old.description, 
        'iwork_id', 
        old.iwork_id::text ); 
    end if;

    -- cofk_union_work 39. editors_notes
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'editors_notes', 
        new.editors_notes::text, 
        old.editors_notes::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'editors_notes', 
        new.editors_notes::text ); 
    end if;

    -- cofk_union_work 40. edit_status
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'edit_status', 
        new.edit_status::text, 
        old.edit_status::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'edit_status', 
        new.edit_status::text ); 
    end if;

    -- cofk_union_work 41. relevant_to_cofk
    if TG_OP = 'UPDATE' then 
      perform dbf_cofk_union_audit_literal_update( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'relevant_to_cofk', 
        new.relevant_to_cofk::text, 
        old.relevant_to_cofk::text ); 
    end if;

    if TG_OP = 'INSERT' then 
      perform dbf_cofk_union_audit_literal_insert( 'cofk_union_work', 
        new.work_id, 
        new.iwork_id, 
        new.description, 
        'relevant_to_cofk', 
        new.relevant_to_cofk::text ); 
    end if;

  end if; -- End of cofk_union_work

-------------------

  if TG_RELNAME = 'INSERT' or TG_RELNAME = 'UPDATE' then
    return new;
  else
    return old;
  end if;
end;
$$;


ALTER FUNCTION public.dbf_cofk_union_audit_any() OWNER TO postgres;

--
-- Name: dbf_cofk_union_audit_literal_delete(character varying, character varying, integer, text, character varying, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_audit_literal_delete(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_old_column_value text) RETURNS void
    LANGUAGE plpgsql
    AS $$

declare
  carriage_return constant varchar(1) = E'\r';
  newline constant varchar(1) = E'\n';
begin

  if trim( replace( replace( coalesce( input_old_column_value, '' ), carriage_return, '' ), newline, '' )) > ''
  then

    insert into cofk_union_audit_literal(
      change_type,
      table_name,
      key_value_text      ,
      key_value_integer   ,
      key_decode          ,
      column_name         ,
      old_column_value    
    )
    values (
      'Del',
      input_table_name          ,
      input_key_value_text      ,
      input_key_value_integer   ,
      input_key_decode          ,
      input_column_name         ,
      input_old_column_value    
    );

  end if;

  return;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_audit_literal_delete(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_old_column_value text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_audit_literal_insert(character varying, character varying, integer, text, character varying, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_audit_literal_insert(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_new_column_value text) RETURNS void
    LANGUAGE plpgsql
    AS $$

declare
  carriage_return constant varchar(1) = E'\r';
  newline constant varchar(1) = E'\n';
begin

  if trim( replace( replace( coalesce( input_new_column_value, '' ), carriage_return, '' ), newline, '' )) > ''
  then

    insert into cofk_union_audit_literal(
      change_type,
      table_name,
      key_value_text      ,
      key_value_integer   ,
      key_decode          ,
      column_name         ,
      new_column_value    
    )
    values (
      'New',
      input_table_name          ,
      input_key_value_text      ,
      input_key_value_integer   ,
      input_key_decode          ,
      input_column_name         ,
      input_new_column_value    
    );

  end if;

  return;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_audit_literal_insert(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_new_column_value text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_audit_literal_update(character varying, character varying, integer, text, character varying, text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_audit_literal_update(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_new_column_value text, input_old_column_value text) RETURNS void
    LANGUAGE plpgsql
    AS $$

declare
  carriage_return constant varchar(1) = E'\r';
  newline constant varchar(1) = E'\n';
begin

  if trim( replace( replace( coalesce( input_new_column_value, '' ), carriage_return, '' ), newline, '' )) 
  != trim( replace( replace( coalesce( input_old_column_value, '' ), carriage_return, '' ), newline, '' )) then

    insert into cofk_union_audit_literal(
      change_type,
      table_name,
      key_value_text      ,
      key_value_integer   ,
      key_decode          ,
      column_name         ,
      new_column_value    ,
      old_column_value    
    )
    values (
      'Chg',
      input_table_name          ,
      input_key_value_text      ,
      input_key_value_integer   ,
      input_key_decode          ,
      input_column_name         ,
      input_new_column_value    ,
      input_old_column_value    
    );

  end if;

  return;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_audit_literal_update(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_new_column_value text, input_old_column_value text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_audit_relationship_delete(character varying, character varying, text, character varying, character varying, character varying, character varying, character varying, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_audit_relationship_delete(input_left_table_name character varying, input_left_id_value_old character varying, input_left_id_decode_old text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_old character varying, input_right_id_decode_old text) RETURNS void
    LANGUAGE plpgsql
    AS $$

begin

  insert into cofk_union_audit_relationship (
    change_type,
    left_table_name,
    left_id_value_old,
    left_id_decode_old,
    relationship_type,
    relationship_decode_left_to_right,
    relationship_decode_right_to_left,
    right_table_name,
    right_id_value_old,
    right_id_decode_old
  )
  values (
    'Del',
    input_left_table_name,
    input_left_id_value_old,
    input_left_id_decode_old,
    input_relationship_type,
    input_relationship_decode_left_to_right,
    input_relationship_decode_right_to_left,
    input_right_table_name,
    input_right_id_value_old,
    input_right_id_decode_old
  );

  return;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_audit_relationship_delete(input_left_table_name character varying, input_left_id_value_old character varying, input_left_id_decode_old text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_old character varying, input_right_id_decode_old text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_audit_relationship_insert(character varying, character varying, text, character varying, character varying, character varying, character varying, character varying, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_audit_relationship_insert(input_left_table_name character varying, input_left_id_value_new character varying, input_left_id_decode_new text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_new character varying, input_right_id_decode_new text) RETURNS void
    LANGUAGE plpgsql
    AS $$

begin

  insert into cofk_union_audit_relationship (
    change_type,
    left_table_name,
    left_id_value_new,
    left_id_decode_new,
    relationship_type,
    relationship_decode_left_to_right,
    relationship_decode_right_to_left,
    right_table_name,
    right_id_value_new,
    right_id_decode_new
  )
  values (
    'New',
    input_left_table_name,
    input_left_id_value_new,
    input_left_id_decode_new,
    input_relationship_type,
    input_relationship_decode_left_to_right,
    input_relationship_decode_right_to_left,
    input_right_table_name,
    input_right_id_value_new,
    input_right_id_decode_new
  );

  return;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_audit_relationship_insert(input_left_table_name character varying, input_left_id_value_new character varying, input_left_id_decode_new text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_new character varying, input_right_id_decode_new text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_audit_relationship_update(character varying, character varying, text, character varying, text, character varying, character varying, character varying, character varying, character varying, text, character varying, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_audit_relationship_update(input_left_table_name character varying, input_left_id_value_new character varying, input_left_id_decode_new text, input_left_id_value_old character varying, input_left_id_decode_old text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_new character varying, input_right_id_decode_new text, input_right_id_value_old character varying, input_right_id_decode_old text) RETURNS void
    LANGUAGE plpgsql
    AS $$

begin

  insert into cofk_union_audit_relationship (
    change_type,
    left_table_name,
    left_id_value_new,
    left_id_decode_new,
    left_id_value_old,
    left_id_decode_old,
    relationship_type,
    relationship_decode_left_to_right,
    relationship_decode_right_to_left,
    right_table_name,
    right_id_value_new,
    right_id_decode_new,
    right_id_value_old,
    right_id_decode_old
  )
  values (
    'Chg',
    input_left_table_name,
    input_left_id_value_new,
    input_left_id_decode_new,
    input_left_id_value_old,
    input_left_id_decode_old,
    input_relationship_type,
    input_relationship_decode_left_to_right,
    input_relationship_decode_right_to_left,
    input_right_table_name,
    input_right_id_value_new,
    input_right_id_decode_new,
    input_right_id_value_old,
    input_right_id_decode_old
  );

  return;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_audit_relationship_update(input_left_table_name character varying, input_left_id_value_new character varying, input_left_id_decode_new text, input_left_id_value_old character varying, input_left_id_decode_old text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_new character varying, input_right_id_decode_new text, input_right_id_value_old character varying, input_right_id_decode_old text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_cascade01_work_changes(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_cascade01_work_changes() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

begin


  if TG_OP = 'INSERT' then
    insert into cofk_union_queryable_work( work_id, iwork_id ) values ( new.work_id, new.iwork_id );
  end if;

  if TG_OP = 'INSERT' or TG_OP = 'UPDATE' then
    perform dbf_cofk_union_refresh_work_desc( new.work_id );
    perform dbf_cofk_union_refresh_queryable_work( new.work_id );
    return new;

  elsif TG_OP = 'DELETE' then
    delete from cofk_union_queryable_work where work_id = old.work_id;
    delete from cofk_union_relationship where left_table_name  = 'cofk_union_work' and left_id_value  = old.work_id;
    delete from cofk_union_relationship where right_table_name = 'cofk_union_work' and right_id_value = old.work_id;
    return old;
  end if;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_cascade01_work_changes() OWNER TO postgres;

--
-- Name: dbf_cofk_union_cascade02_rel_changes(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_cascade02_rel_changes() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

declare
  work_table constant varchar(30) = 'cofk_union_work';
  manif_table constant varchar(30) = 'cofk_union_manifestation';
  repos_table constant varchar(30) = 'cofk_union_institution';
  image_table constant varchar(30) = 'cofk_union_image';
  work_id varchar(100);
  manifestation_id varchar(100);
begin

  --------------------------------------------------------------------------------
  -- Refresh work description and 'queryable' work table
  -- when people, places, manifestations, comments, images or related works change
  --------------------------------------------------------------------------------
  if TG_OP = 'INSERT' then

    ---- Work insert ----

    if new.left_table_name = work_table then
      perform dbf_cofk_union_refresh_work_desc( new.left_id_value );
      perform dbf_cofk_union_refresh_queryable_work( new.left_id_value );
    end if;

    if new.right_table_name = work_table then
      perform dbf_cofk_union_refresh_work_desc( new.right_id_value );
      perform dbf_cofk_union_refresh_queryable_work( new.right_id_value );
    end if;


    ---- Manifestation/repository insert ----

    if new.left_table_name = manif_table 
    and ( new.right_table_name = repos_table   -- manif stored in institution
    or    new.right_table_name = manif_table ) -- manif enclosed in another manif
    then 
      select right_id_value into work_id 
      from cofk_union_relationship
      where left_table_name = manif_table -- manifestation is manifestation of work
      and left_id_value = new.left_id_value
      and relationship_type = 'is_manifestation_of'
      and right_table_name = work_table;

      perform dbf_cofk_union_refresh_queryable_work( work_id );

      if new.right_table_name = manif_table then -- manif enclosed in another manif
        select right_id_value into work_id 
        from cofk_union_relationship
        where left_table_name = manif_table -- manifestation is manifestation of work
        and left_id_value = new.right_id_value
        and relationship_type = 'is_manifestation_of'
        and right_table_name = work_table;

        perform dbf_cofk_union_refresh_queryable_work( work_id );
      end if;

    ---- Image/manifestation insert ----
    elseif new.left_table_name = image_table and new.right_table_name = manif_table 
    then 
      select right_id_value into work_id 
      from cofk_union_relationship
      where left_table_name = manif_table -- manifestation is manifestation of work
      and left_id_value = new.right_id_value
      and relationship_type = 'is_manifestation_of'
      and right_table_name = work_table;

      perform dbf_cofk_union_refresh_queryable_work( work_id );
    end if;


    ---- Person insert ----

    if new.left_table_name = 'cofk_union_person' then
      perform dbf_cofk_union_refresh_person_summary( new.left_id_value );
    end if;

    if new.right_table_name = 'cofk_union_person' then
      perform dbf_cofk_union_refresh_person_summary( new.right_id_value );
    end if;

    return new;

  elsif TG_OP = 'UPDATE' then

    ---- Work update ----

    if old.left_table_name = work_table then
      perform dbf_cofk_union_refresh_work_desc( old.left_id_value );
      perform dbf_cofk_union_refresh_queryable_work( old.left_id_value );
    end if;

    if new.left_table_name = work_table and new.left_id_value != old.left_id_value then
      perform dbf_cofk_union_refresh_work_desc( new.left_id_value );
      perform dbf_cofk_union_refresh_queryable_work( new.left_id_value );
    end if;

    if old.right_table_name = work_table then
      perform dbf_cofk_union_refresh_work_desc( old.right_id_value );
      perform dbf_cofk_union_refresh_queryable_work( old.right_id_value );
    end if;

    if new.right_table_name = work_table and new.right_id_value != old.right_id_value then
      perform dbf_cofk_union_refresh_work_desc( new.right_id_value );
      perform dbf_cofk_union_refresh_queryable_work( new.right_id_value );
    end if;

    ---- Person update ----

    if old.left_table_name = 'cofk_union_person' then
      perform dbf_cofk_union_refresh_person_summary( old.left_id_value );
    end if;

    if new.left_table_name = 'cofk_union_person' and new.left_id_value != old.left_id_value then
      perform dbf_cofk_union_refresh_person_summary( new.left_id_value );
    end if;

    if old.right_table_name = 'cofk_union_person' then
      perform dbf_cofk_union_refresh_person_summary( old.right_id_value );
    end if;

    if new.right_table_name = 'cofk_union_person' and new.right_id_value != old.right_id_value then
      perform dbf_cofk_union_refresh_person_summary( new.right_id_value );
    end if;

    ---- Manifestation update of dates of former owner, where studied, etc. ----
    manifestation_id = '';

    if old.left_table_name = manif_table and ( coalesce( old.relationship_valid_from, '9999-12-31'::date ) != 
                                               coalesce( new.relationship_valid_from, '9999-12-31'::date ) 
                                               or
                                               coalesce( old.relationship_valid_till, '9999-12-31'::date ) != 
                                               coalesce( new.relationship_valid_till, '9999-12-31'::date )) 
    then
      manifestation_id = old.left_id_value;

      select right_id_value into work_id 
      from cofk_union_relationship
      where left_table_name = manif_table -- manifestation is manifestation of work
      and left_id_value = manifestation_id
      and relationship_type = 'is_manifestation_of'
      and right_table_name = work_table;

      perform dbf_cofk_union_refresh_queryable_work( work_id );
    end if;

    if old.right_table_name = manif_table and ( coalesce( old.relationship_valid_from, '9999-12-31'::date ) != 
                                                coalesce( new.relationship_valid_from, '9999-12-31'::date ) 
                                                or
                                                coalesce( old.relationship_valid_till, '9999-12-31'::date ) != 
                                                coalesce( new.relationship_valid_till, '9999-12-31'::date )) 
    then
      manifestation_id = old.right_id_value;

      select right_id_value into work_id 
      from cofk_union_relationship
      where left_table_name = manif_table -- manifestation is manifestation of work
      and left_id_value = manifestation_id
      and relationship_type = 'is_manifestation_of'
      and right_table_name = work_table;

      perform dbf_cofk_union_refresh_queryable_work( work_id );
    end if;

    return new;

  --------------------------------------------------------------------
  -- Delete from relationships table if people, places etc are deleted
  --------------------------------------------------------------------
  elsif TG_OP = 'DELETE' then

    ---- Work delete ----

    if old.left_table_name = work_table then
      perform dbf_cofk_union_refresh_work_desc( old.left_id_value );
      perform dbf_cofk_union_refresh_queryable_work( old.left_id_value );
    end if;

    if old.right_table_name = work_table then
      perform dbf_cofk_union_refresh_work_desc( old.right_id_value );
      perform dbf_cofk_union_refresh_queryable_work( old.right_id_value );
    end if;


    ---- Manifestation/repository delete ----

    if old.left_table_name = manif_table 
    and ( old.right_table_name = repos_table   -- manif stored in institution
    or    old.right_table_name = manif_table ) -- manif enclosed in another manif
    then 
      select right_id_value into work_id 
      from cofk_union_relationship
      where left_table_name = manif_table -- manifestation is manifestation of work
      and left_id_value = old.left_id_value
      and relationship_type = 'is_manifestation_of'
      and right_table_name = work_table;

      perform dbf_cofk_union_refresh_queryable_work( work_id );

      if old.right_table_name = manif_table then -- manif enclosed in another manif
        select right_id_value into work_id 
        from cofk_union_relationship
        where left_table_name = manif_table -- manifestation is manifestation of work
        and left_id_value = old.right_id_value
        and relationship_type = 'is_manifestation_of'
        and right_table_name = work_table;

        perform dbf_cofk_union_refresh_queryable_work( work_id );
      end if;
    end if;


    ---- Person delete ----

    if old.left_table_name = 'cofk_union_person' then
      perform dbf_cofk_union_refresh_person_summary( old.left_id_value );
    end if;

    if old.right_table_name = 'cofk_union_person' then
      perform dbf_cofk_union_refresh_person_summary( old.right_id_value );
    end if;


    ---- Image/manifestation delete ----
    if old.left_table_name = image_table and old.right_table_name = manif_table 
    then 
      select right_id_value into work_id 
      from cofk_union_relationship
      where left_table_name = manif_table -- manifestation is manifestation of work
      and left_id_value = old.right_id_value
      and relationship_type = 'is_manifestation_of'
      and right_table_name = work_table;

      perform dbf_cofk_union_refresh_queryable_work( work_id );
    end if;
    return old;
  end if;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_cascade02_rel_changes() OWNER TO postgres;

--
-- Name: dbf_cofk_union_cascade03_decodes(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_cascade03_decodes() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

declare
  other_side record;
  known_id varchar(100);
  work_id_var varchar(100);
begin

  --------------------------------------------------------------------------------
  -- Refresh work description and 'queryable' work table
  -- when people, places, manifestations, comments, images or related works change
  -- and 'other details summary' of people when related people, places etc change.
  --------------------------------------------------------------------------------
  if TG_OP = 'INSERT' then
    if TG_RELNAME = 'cofk_union_person' then
      insert into cofk_union_person_summary( iperson_id ) values ( new.iperson_id );
    end if;
    return new;

  elsif TG_OP = 'UPDATE' then  -- no need to do this for inserts, because there are no relationships set up yet

    if TG_RELNAME = 'cofk_union_person' then
      known_id = new.person_id;
    elsif TG_RELNAME = 'cofk_union_location' then
      known_id = new.location_id;
    elsif TG_RELNAME = 'cofk_union_manifestation' then
      known_id = new.manifestation_id;
    elsif TG_RELNAME = 'cofk_union_comment' then
      known_id = new.comment_id::varchar;
    elsif TG_RELNAME = 'cofk_union_image' then
      known_id = new.image_id::varchar;
    elsif TG_RELNAME = 'cofk_union_work' then
      known_id = new.work_id;
    elsif TG_RELNAME = 'cofk_union_resource' then
      known_id = new.resource_id::varchar;
    elsif TG_RELNAME = 'impt_nisba' then -- an IMPAcT-specific table
      known_id = new.nisba_id::varchar;
    end if;

    if known_id is not null then
      --------------------------------------------------------------------------------
      -- Refresh image summary in queryable work if filename or thumbnail has changed.
      -- (No other image data is currently shown in the queryable summary.)
      --------------------------------------------------------------------------------
      if TG_RELNAME = 'cofk_union_image' then 
        if coalesce( old.image_filename, '' ) != coalesce( new.image_filename, '' )
        or coalesce( old.thumbnail, '' ) != coalesce( new.thumbnail, '' ) 
        then
          for other_side in 
          select * from dbf_cofk_union_list_rel_ids ( 'cofk_union_manifestation',  -- required table
                                                       '%',                 -- required relationship type, i.e. any
                                                       TG_RELNAME::varchar, -- known table 
                                                       known_id )
          loop
            select right_id_value into work_id_var 
            from cofk_union_relationship
            where left_table_name = 'cofk_union_manifestation'
            and left_id_value = other_side.id_value
            and relationship_type = 'is_manifestation_of'
            and right_table_name = 'cofk_union_work';

            perform dbf_cofk_union_refresh_queryable_work( work_id_var );
          end loop;
          return new;  -- no need to do anything else for changes to images, they only relate to manifestations
        end if;
      end if;

      ----------------
      -- Refresh works
      ----------------
      for other_side in 
      select * from dbf_cofk_union_list_rel_ids ( 'cofk_union_work', -- required table
                                                   '%',                -- required relationship type, i.e. any
                                                   TG_RELNAME::varchar,         -- known table 
                                                   known_id )
      loop
        if other_side.id_value like 'SeldenEndFindAidcofk_import_ead-ead_c01_id%' then -- no need to change index card
          continue;
        end if;

        perform dbf_cofk_union_refresh_work_desc( other_side.id_value );
        perform dbf_cofk_union_refresh_queryable_work( other_side.id_value );
      end loop;

      -----------------
      -- Refresh people
      -----------------
      if TG_RELNAME != 'cofk_union_work' then -- we don't include works in person summary: too unwieldy
        for other_side in 
        select * from dbf_cofk_union_list_rel_ids ( 'cofk_union_person',  -- required table
                                                     '%',                 -- required relationship type, i.e. any
                                                     TG_RELNAME::varchar, -- known table 
                                                     known_id )
        loop
          perform dbf_cofk_union_refresh_person_summary( other_side.id_value );
        end loop;
      end if;

    end if;

    return new;

  --------------------------------------------------------------------
  -- Delete from relationships table if people, places etc are deleted
  --------------------------------------------------------------------
  elsif TG_OP = 'DELETE' then

    if TG_RELNAME = 'cofk_union_person' then
      known_id = old.person_id;
    elsif TG_RELNAME = 'cofk_union_location' then
      known_id = old.location_id::varchar;
    elsif TG_RELNAME = 'cofk_union_manifestation' then
      known_id = old.manifestation_id;
    elsif TG_RELNAME = 'cofk_union_comment' then
      known_id = old.comment_id::varchar;
    elsif TG_RELNAME = 'cofk_union_image' then
      known_id = old.image_id::varchar;
    elsif TG_RELNAME = 'cofk_union_institution' then
      known_id = old.institution_id::varchar;
    elsif TG_RELNAME = 'cofk_union_resource' then
      known_id = old.resource_id::varchar;
    elsif TG_RELNAME = 'cofk_union_work' then
      known_id = old.work_id;
    elsif TG_RELNAME = 'impt_nisba' then -- an IMPAcT-specific table
      known_id = old.nisba_id::varchar;
    end if;

    if known_id is not null then  -- delete of relationships will trigger refresh of work descriptions etc.

      delete from cofk_union_relationship
      where left_table_name = TG_RELNAME
      and left_id_value = known_id;

      delete from cofk_union_relationship
      where right_table_name = TG_RELNAME
      and right_id_value = known_id;

    end if;

    return old;
  end if;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_cascade03_decodes() OWNER TO postgres;

--
-- Name: dbf_cofk_union_convert_list_from_html(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_convert_list_from_html(input_string text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_convert_list_to_or_from_html( input_string, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_convert_list_from_html(input_string text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_convert_list_to_html(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_convert_list_to_html(input_string text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_convert_list_to_or_from_html( input_string, 1 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_convert_list_to_html(input_string text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_convert_list_to_or_from_html(text, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_convert_list_to_or_from_html(input_string text, html_output integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  output_string text;

  html_list_start_marker constant varchar(12) not null 
  default dbf_cofk_union_get_constant_value('html_list_start_marker'); -- <ul><li>

  html_list_item_marker  constant varchar(12) not null 
  default dbf_cofk_union_get_constant_value('html_list_item_marker' ); -- </li><li>

  html_list_end_marker   constant varchar(12) not null 
  default dbf_cofk_union_get_constant_value('html_list_end_marker'  ); -- </li></ul>

  non_html_list_item_marker  constant varchar(12) not null 
  default dbf_cofk_union_get_constant_value( 'list_item_marker'  ); --  ~ (tilde)
begin

  output_string = input_string;

  if html_output = 1 then
    if strpos( output_string, non_html_list_item_marker ) = 0 then
      return output_string;
    end if;
    output_string = replace( output_string, non_html_list_item_marker, html_list_item_marker );
    output_string = html_list_start_marker || output_string || html_list_end_marker;
  else
    output_string = replace( output_string, html_list_item_marker, non_html_list_item_marker );
    output_string = replace( output_string, html_list_start_marker, '' );
    output_string = replace( output_string, html_list_end_marker, '' );
  end if;

  return output_string;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_convert_list_to_or_from_html(input_string text, html_output integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode(character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$
begin

  return dbf_cofk_union_decode( table_name, key_value, 0 );  -- 0 means no suppression of links
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode(character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying, suppress_links integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_decode( table_name, key_value, suppress_links, 0 );  -- 0 means no extra details
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying, suppress_links integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode(character varying, character varying, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying, suppress_links integer, expand_details integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  statement text;
  decode text;
  newline constant varchar(1) = E'\n';
begin

  decode = '';

  if table_name = 'cofk_union_person' then
    return dbf_cofk_union_decode_person( key_value, suppress_links, expand_details );

  elsif table_name = 'cofk_union_work' or table_name = 'cofk_union_queryable_work' then
    return dbf_cofk_union_decode_work( key_value, suppress_links );

  elsif table_name = 'cofk_union_manifestation' then
    return dbf_cofk_union_decode_manifestation( key_value, suppress_links );

  elsif table_name = 'cofk_nisba' then -- in practice only used by IMPAcT
    return dbf_cofk_union_decode_nisba( key_value::integer, suppress_links );

  elsif table_name = 'cofk_union_location' then
    if expand_details = 1 then
      statement = 'select location_name || case when location_synonyms > '''' then '
                || ' ''' || newline || '('' || location_synonyms || '')'' else '''' end '
                || ' from ' || table_name || ' where location_id = ' || key_value;
    else
      statement = 'select location_name from ' || table_name || ' where location_id = ' || key_value;
    end if;

  elsif table_name = 'cofk_union_relationship_type' then
    statement = 'select desc_left_to_right from ' || table_name 
              || ' where relationship_code = ''' || key_value || '''';

  elsif table_name = 'cofk_union_comment' then
    statement = 'select comment from ' || table_name || ' where comment_id = ' || key_value;

  elsif table_name = 'cofk_union_event' then
    statement = 'select event_desc from ' || table_name || ' where event_id = ' || key_value;

  elsif table_name = 'cofk_union_image' then
    statement = 'select image_filename from ' || table_name || ' where image_id = ' || key_value;

  elsif table_name = 'cofk_union_institution' then
    statement = 'select institution_name from ' || table_name || ' where institution_id = ' || key_value;

  elsif table_name = 'cofk_union_resource' then
    statement = 'select resource_name from ' || table_name || ' where resource_id = ' || key_value;

  elsif table_name = 'cofk_union_publication' then
    statement = 'select publication_details from ' || table_name || ' where publication_id = ' || key_value;

  elsif table_name = 'cofk_union_subject' then
    statement = 'select subject_desc from ' || table_name || ' where subject_id = ' || key_value;

  elsif table_name = 'cofk_union_nationality' then
    statement = 'select nationality_desc from ' || table_name || ' where nationality_id = ' || key_value;

  elsif table_name = 'cofk_union_role_category' then
    statement = 'select role_category_desc from ' || table_name || ' where role_category_id = ' || key_value;

  else
    return 'Cannot decode ' || table_name || ' ' || key_value;
  end if;

  execute statement into decode;

  if decode is NULL then
    decode = 'Empty or missing value in ' || table_name || ' with key ' || key_value; 
  end if;

  return decode;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying, suppress_links integer, expand_details integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode_expanded(character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode_expanded(table_name character varying, key_value character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$
begin

  return dbf_cofk_union_decode( table_name, key_value, 0, 1 );  -- don't suppress links, do expand details
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_decode_expanded(table_name character varying, key_value character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode_expanded(character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode_expanded(table_name character varying, key_value character varying, suppress_links integer) RETURNS text
    LANGUAGE plpgsql
    AS $$
begin

  return dbf_cofk_union_decode( table_name, key_value, suppress_links, 1 );  -- 1 = expand details
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_decode_expanded(table_name character varying, key_value character varying, suppress_links integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode_manifestation(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode_manifestation(key_value character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_decode_manifestation( key_value, 0 );  -- 0 means 'don't suppress links'
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_decode_manifestation(key_value character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode_manifestation(character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode_manifestation(key_value character varying, suppress_links integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  decode text;
  integer_key integer;
  manif_date varchar(32);
  date_inferred smallint;
  date_uncertain smallint;
  date_approx smallint;
begin

  decode = '';

  select trim( coalesce( id_number_or_shelfmark, '' ) || ' ' 
            || coalesce( printed_edition_details, '' )),
            manifestation_creation_date::varchar,
            manifestation_creation_date_inferred,
            manifestation_creation_date_uncertain,
            manifestation_creation_date_approx
            into decode, manif_date, date_inferred, date_uncertain, date_approx
            from cofk_union_manifestation
            where manifestation_id = key_value;
  if manif_date > '' and manif_date != '9999-12-31' then
    if date_approx > 0 then
      manif_date = 'c.' || manif_date;
    end if;
    if date_uncertain > 0 then
      manif_date = manif_date || '?';
    end if;
    if date_inferred > 0 then
      manif_date = '[' || manif_date || ']';
    end if;
    decode = manif_date || ': ' || decode;
  end if;

  if suppress_links = 1 then
    return decode;
  end if;

  select w.iwork_id into integer_key
  from cofk_union_work w, cofk_union_relationship r
  where r.left_table_name = 'cofk_union_manifestation'
  and r.left_id_value = key_value
  and r.relationship_type = 'is_manifestation_of'
  and r.right_table_name = 'cofk_union_work'
  and r.right_id_value = w.work_id;

  select dbf_cofk_union_link_to_edit_app( decode, 
                                          '?iwork_id=' || integer_key::varchar ) into decode;

  return decode;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_decode_manifestation(key_value character varying, suppress_links integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode_person(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode_person(key_value character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_decode_person( key_value, 0 );  -- 0 means 'don't suppress links', but this may not be relevant
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_decode_person(key_value character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode_person(character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode_person(key_value character varying, suppress_links integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_decode_person( key_value, 0, 0 );  -- First 0 means 'don't suppress links'
end;                                                       -- Second 0 means 'don't expand details'

$$;


ALTER FUNCTION public.dbf_cofk_union_decode_person(key_value character varying, suppress_links integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode_person(character varying, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode_person(key_value character varying, suppress_links integer, expand_details integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  decode text;
  person record;
  shortstring varchar(15);
  display_year varchar(15);
begin

  decode = '';

  select * from cofk_union_person into person where person_id = key_value;

  if person.is_organisation > '' then
    if person.organisation_type > 0 then
      shortstring = person.organisation_type::varchar;

      select ' (' || org_type_desc || ')' into decode
      from cofk_union_org_type
      where org_type_id = shortstring::integer;
    end if;
  end if;

  decode = trim( person.foaf_name ) || decode;

  -----------------------------------
  -- Both birth and death dates known
  -----------------------------------
  if  (person.date_of_birth_year is not null or person.date_of_birth2_year is not null)
  and (person.date_of_death_year is not null or person.date_of_death2_year is not null) then

    decode = decode || ', ';

    if person.date_of_birth2_year is not null then
      display_year = person.date_of_birth2_year::varchar || ' or before';
    else
      display_year = person.date_of_birth_year::varchar;
      if person.date_of_birth_is_range = 1 then
        display_year = display_year || ' or after';
      end if;
    end if;

    decode = decode || display_year;

    decode = decode || '-';

    if person.date_of_death2_year is not null then
      display_year = person.date_of_death2_year::varchar || ' or before';
    else
      display_year = person.date_of_death_year::varchar;
      if person.date_of_death_is_range = 1 then
        display_year = display_year || ' or after';
      end if;
    end if;

    decode = decode || display_year;

  ------------------------
  -- Only birth date known
  ------------------------
  elsif person.date_of_birth_year is not null or person.date_of_birth2_year is not null then

    shortstring = ', b.';
    if person.is_organisation = 'Y' then
      shortstring = ', formed ';
    end if;

    if person.date_of_birth2_year is not null then
      display_year = person.date_of_birth2_year::varchar || ' or before';
    else
      display_year = person.date_of_birth_year::varchar;
      if person.date_of_birth_is_range = 1 then
        display_year = display_year || ' or after';
      end if;
    end if;

    decode = decode || shortstring || display_year;

  ------------------------
  -- Only death date known
  ------------------------
  elsif person.date_of_death_year is not null or person.date_of_death2_year is not null then

    shortstring = ', d.';
    if person.is_organisation = 'Y' then
      shortstring = ', disbanded ';
    end if;

    if person.date_of_death2_year is not null then
      display_year = person.date_of_death2_year::varchar || ' or before';
    else
      display_year = person.date_of_death_year::varchar;
      if person.date_of_death_is_range = 1 then
        display_year = display_year || ' or after';
      end if;
    end if;

    decode = decode || shortstring || display_year;
  end if;

  ------------------------------
  -- Flourished dates known
  ------------------------------
  if person.flourished_year is not null or person.flourished2_year is not null then
    shortstring = ', fl. ';

    if person.flourished_year is not null and person.flourished2_year is not null then
      decode = decode || shortstring || person.flourished_year::text || '-' || person.flourished2_year::text;

    elsif person.flourished_year is not null then
      decode = decode || shortstring || person.flourished_year::text;
      if person.flourished_is_range = 1 then
        decode = decode || ' and after';
      end if;

    elsif person.flourished2_year is not null then
      decode = decode || shortstring || ' until ' || person.flourished2_year::text;
    end if;
  end if;

  -------------------------
  -- Add alternative names?
  -------------------------
  if expand_details = 1 then
    if person.skos_altlabel > '' then
      decode = decode || '; alternative name(s): ' || person.skos_altlabel;
    end if;
  end if;

  return decode;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_decode_person(key_value character varying, suppress_links integer, expand_details integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode_place_with_region(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode_place_with_region(place_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  location_table constant varchar(30) = 'cofk_union_location';
  in_region constant varchar(20) = 'is_in_or_near';

  placename text;
  outer_regions record;
  placename_and_region text not null default '';

  newline constant varchar(1) = E'\n';
begin


  -- Get basic place name
  select location_name || case when location_synonyms > '' then newline || '(' || location_synonyms || ')' else '' end
  into placename 
  from cofk_union_location 
  where location_id = place_id;

  placename := trim( coalesce( placename, '' ));

  placename_and_region := placename;

  -- Find which region(s) this place is in
  for outer_regions in select right_id_value as region_id
                    from 
                      cofk_union_relationship 
                    where 
                      left_table_name = location_table  -- place is in region, so place=left, region=right
                      and left_id_value = place_id
                      and relationship_type = in_region
                      and right_table_name = location_table
                    order by relationship_id
  loop
    placename := dbf_cofk_union_decode_place_with_region( outer_regions.region_id );

    if placename_and_region > '' and placename > '' then
      placename_and_region := placename_and_region || ', ';
    end if;

    placename_and_region := placename_and_region || placename;
  end loop;

  return placename_and_region;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_decode_place_with_region(place_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode_work(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode_work(key_value character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_decode_work( key_value, 0 );  -- 0 means 'don't suppress links'
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_decode_work(key_value character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode_work(character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode_work(key_value character varying, suppress_links integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  decode text;
  integer_key integer;
begin

  decode = '';

  select iwork_id, coalesce( description, '' ) 
  into integer_key, decode
  from cofk_union_work where work_id = key_value;
  if suppress_links = 1 then
    return decode;
  end if;

  if integer_key is not null then
    select dbf_cofk_union_link_to_edit_app( decode, '?iwork_id=' || integer_key::varchar )
    into decode;
  end if;

  return decode;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_decode_work(key_value character varying, suppress_links integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_decode_work_role_with_aliases(character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_decode_work_role_with_aliases(role_code character varying, input_work_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  people_with_role record;
  basic_name text;
  aliases text;
  altlabels text;
  names_and_aliases text not null default '';
begin

  for people_with_role in 
  select * from dbf_cofk_union_list_rel_ids ( 'cofk_union_person', -- required table
                                               role_code,            -- required relationship
                                               'cofk_union_work',   -- known table 
                                               input_work_id )       -- known ID
  loop
    if names_and_aliases > '' then
      names_and_aliases := names_and_aliases || ' ~ ';
    end if;

    -- Get normal description for person
    basic_name = dbf_cofk_union_decode( 'cofk_union_person', people_with_role.id_value );
    basic_name = coalesce( basic_name, '' );

    names_and_aliases := names_and_aliases || basic_name;

    ------------------------------------------------------------------------------------------

    -- Add alternative spellings etc
    select skos_altlabel 
    into altlabels 
    from cofk_union_person
    where person_id = people_with_role.id_value;

    altlabels = trim( altlabels );

    if altlabels > '' then
      names_and_aliases := names_and_aliases || ', also known as: ' || altlabels;
    end if;

    ------------------------------------------------------------------------------------------

    -- Add extra aliases
    select person_aliases 
    into aliases 
    from cofk_union_person
    where person_id = people_with_role.id_value;

    aliases = trim( aliases );

    if aliases > '' then
      names_and_aliases := names_and_aliases || ' (titles/roles: ' || aliases || ')';
    end if;
  end loop;

  return names_and_aliases;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_decode_work_role_with_aliases(role_code character varying, input_work_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_full_manifestation_details(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_full_manifestation_details(input_work_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  manifestation_id_rec record;
  enclos_id_rec record;
  shelfmark text;
  repository text;
  decode text;
  postage_marks_var text;
  incipit_var text;
  excipit_var text;
  manifestation_summary text not null default '';
  newline constant varchar(1) = E'\n';
begin

  for manifestation_id_rec in 
  select * from dbf_cofk_union_list_rel_ids ( 'cofk_union_manifestation', -- required table
                                               'is_manifestation_of',       -- required relationship
                                               'cofk_union_work',   -- known table 
                                               input_work_id )       -- known ID
  loop
    if manifestation_summary > '' then
      manifestation_summary := manifestation_summary || ' -- ';
    end if;

    -- Get manifestation type
    select d.document_type_desc
    into decode
    from cofk_union_manifestation m, cofk_lookup_document_type d
    where d.document_type_code = m.manifestation_type
    and m.manifestation_id =  manifestation_id_rec.id_value;

    if decode > '' then
      manifestation_summary = manifestation_summary || decode || '. ';
    end if;
    decode = '';

    -- Get manifestation type
    select postage_marks, manifestation_incipit, manifestation_excipit
    into postage_marks_var, incipit_var, excipit_var
    from cofk_union_manifestation 
    where manifestation_id =  manifestation_id_rec.id_value;

    if postage_marks_var > '' then
      manifestation_summary = manifestation_summary || 'Postmark: ' || postage_marks_var || '. ';
    end if;
    
    -- Get repository name
    select * into repository
    from dbf_cofk_union_list_rels_decoded( 'cofk_union_institution', -- required table
                                            'stored_in', -- required relationship type
                                            'cofk_union_manifestation', -- known table
                                            manifestation_id_rec.id_value, -- known ID
                                            0 );  -- HTML output
    if repository > '' then
      manifestation_summary = manifestation_summary || repository;
    end if;

    -- Get shelfmark or printed edition details
    -- The third parameter means 'suppress links', as we don't need another link to the current work
    shelfmark = dbf_cofk_union_decode( 'cofk_union_manifestation', manifestation_id_rec.id_value, 1 );

    if repository > '' and shelfmark > '' then
      manifestation_summary = manifestation_summary || ': ';
    end if;

    manifestation_summary := manifestation_summary || shelfmark;

    if dbf_cofk_union_get_constant_value( 'system_prefix' ) = 'impt' then
      if incipit_var > '' then
        manifestation_summary = manifestation_summary || newline || ' ~ Incipit: ' || incipit_var || '. ';
      end if;

      if excipit_var > '' then
        manifestation_summary = manifestation_summary || newline || ' ~ Excipit: ' || excipit_var || '. ';
      end if;
    end if;

    -- Enclosures in this manifestation
    select desc_right_to_left into decode
    from cofk_union_relationship_type
    where relationship_code = 'enclosed_in';

    for enclos_id_rec in 
    select * from dbf_cofk_union_list_lefthand_rel_ids( 'cofk_union_manifestation', -- required table
                                                        'enclosed_in',
                                                        'cofk_union_manifestation', -- known table 
                                                        manifestation_id_rec.id_value ) -- known ID
    loop
      shelfmark = dbf_cofk_union_decode( 'cofk_union_manifestation', enclos_id_rec.id_value );
      if manifestation_summary > '' then
        manifestation_summary := manifestation_summary || newline || ' ~ ';
        manifestation_summary := manifestation_summary || decode /*Had enclosure*/ || ': ' || shelfmark;
      end if;
    end loop;
    decode = '';

    -- Manifestations in which this one was enclosed
    select desc_left_to_right into decode
    from cofk_union_relationship_type
    where relationship_code = 'enclosed_in';

    for enclos_id_rec in 
    select * from dbf_cofk_union_list_righthand_rel_ids('cofk_union_manifestation', -- required table
                                                        'enclosed_in',
                                                        'cofk_union_manifestation', -- known table 
                                                        manifestation_id_rec.id_value ) -- known ID
    loop
      shelfmark = dbf_cofk_union_decode( 'cofk_union_manifestation', enclos_id_rec.id_value );
      if manifestation_summary > '' then
        manifestation_summary := manifestation_summary || newline || ' ~ ';
        manifestation_summary := manifestation_summary || decode /*Was enclosed in*/ || ': ' || shelfmark;
      end if;
    end loop;
  end loop;

  return manifestation_summary;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_full_manifestation_details(input_work_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_get_constant_value(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_get_constant_value(constant_name character varying) RETURNS character varying
    LANGUAGE plpgsql
    AS $$

declare
  constant_value varchar(500) not null default '';

begin

  if constant_name = 'editing_interface_url' then 
    constant_value = 'https://emlo-edit.bodleian.ox.ac.uk/interface/union.php' ;
    if current_database() = 'test' then
      constant_value = replace( constant_value, 'union.php', 'dev_union.php' );
    end if;

  elseif constant_name = 'link_text_start_marker' then 
    constant_value = 'xxxCofkLinkStartxxx' ;

  elseif constant_name = 'link_text_end_marker'   then 
    constant_value = 'xxxCofkLinkEndxxx'   ;

  elseif constant_name = 'href_start_marker'      then 
    constant_value = 'xxxCofkHrefStartxxx' ;

  elseif constant_name = 'href_end_marker'        then 
    constant_value = 'xxxCofkHrefEndxxx'   ;

  elseif constant_name = 'system_prefix'          then 
    constant_value = 'cofk'   ;

  elseif constant_name = 'list_item_marker'       then
    constant_value = ' ~ ';

  elseif constant_name = 'html_list_item_marker'       then
    constant_value = '</li><li>';

  elseif constant_name = 'html_list_start_marker'
  or     constant_name = 'html_ulist_start_marker'     then
    constant_value = '<ul><li>';

  elseif constant_name = 'html_olist_start_marker'     then
    constant_value = '<ol><li>';

  elseif constant_name = 'html_list_end_marker'
  or     constant_name = 'html_ulist_end_marker'       then
    constant_value = '</li></ul>';

  elseif constant_name = 'html_olist_end_marker'       then
    constant_value = '</li></ol>';
  end if;

  return constant_value;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_get_constant_value(constant_name character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_get_language_of_manifestation(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_get_language_of_manifestation(input_manifestation_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  langrec record;
  langstring text not null := '';
  max_chars constant integer = 255;
begin

  for langrec in 
    select iso.language_name, lm.notes
    from iso_639_language_codes iso, cofk_union_language_of_manifestation lm
    where lm.language_code = iso.code_639_3
    and lm.manifestation_id = input_manifestation_id
    order by language_name
  loop
    if langstring > '' then
      langstring = langstring || ', ';
    end if;

    langstring = langstring || langrec.language_name;

    if langrec.notes > '' then
      langstring = langstring || ' (' || langrec.notes || ')';
    end if;
  end loop;

  if length( langstring ) > max_chars then
    langstring = substr( langstring, 1, max_chars );
  end if;

  return langstring;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_get_language_of_manifestation(input_manifestation_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_get_language_of_work(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_get_language_of_work(input_work_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  langrec record;
  langstring text not null := '';
  max_chars constant integer = 255;
begin

  for langrec in 
    select iso.language_name, lw.notes
    from iso_639_language_codes iso, cofk_union_language_of_work lw
    where lw.language_code = iso.code_639_3
    and lw.work_id = input_work_id
    order by language_name
  loop
    if langstring > '' then
      langstring = langstring || ', ';
    end if;

    langstring = langstring || langrec.language_name;

    if langrec.notes > '' then
      langstring = langstring || ' (' || langrec.notes || ')';
    end if;
  end loop;

  if length( langstring ) > max_chars then
    langstring = substr( langstring, 1, max_chars );
  end if;

  return langstring;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_get_language_of_work(input_work_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_get_work_desc(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_get_work_desc(input_work_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  work_desc text not null = '';
  month1 varchar(12) not null = '';
  month2 varchar(12) not null = '';
  date1 varchar(80) not null = '';
  date2 varchar(80) not null = ''; 
  workrec record;
  dummy_date varchar(12);
  relrec record;
  max_notes_length constant integer = 60;
  misc varchar(500);
  newline constant varchar(1) = E'\n';
  carriage_return constant varchar(1) = E'\r';
begin

  work_desc = '';

  select * into workrec from cofk_union_work where work_id = input_work_id;

  ---------------------------
  -- Add dates to description
  ---------------------------
  if workrec.date_of_work_std_month is not null then
    dummy_date = '1000-' || workrec.date_of_work_std_month::varchar || '-01';
    select to_char( dummy_date::date, 'Mon' ) into month1;
  end if;

  if workrec.date_of_work2_std_month is not null then
    dummy_date = '1000-' || workrec.date_of_work2_std_month::varchar || '-01';
    select to_char( dummy_date::date, 'Mon' ) into month2;
  end if;

  date1 = trim( coalesce( workrec.date_of_work_std_day::varchar, '' ) || ' ' || month1 || ' ' 
             || coalesce( workrec.date_of_work_std_year::varchar, '' ) );

  date2 = trim( coalesce( workrec.date_of_work2_std_day::varchar, '' ) || ' ' || month2 || ' ' 
             || coalesce( workrec.date_of_work2_std_year::varchar, '' ) );

  if date1 > '' and date2 > '' then 
    work_desc = date1 || ' to ' || date2;

  elsif date1 > '' then
    if workrec.date_of_work_std_is_range > 0 then
      if workrec.date_of_work_std_day > 0 then
        work_desc = 'On';
      else
        work_desc = 'In';
      end if;
      work_desc = work_desc || ' or after ' || date1;
    else
      work_desc = date1;
    end if;

  elsif date2 > '' then
    if workrec.date_of_work2_std_day > 0 then
      work_desc = 'On';
    else
      work_desc = 'In';
    end if;
    work_desc = work_desc || ' or before ' || date2;

  else
    work_desc = 'Unknown date';
  end if;


  work_desc = work_desc || ': ';

  ------------------------------
  -- Add creators to description
  ------------------------------
  select dbf_cofk_union_list_creators_searchable as namelist
  into relrec
  from dbf_cofk_union_list_creators_searchable( workrec.work_id );

  if relrec.namelist > '' then
    work_desc = work_desc || relrec.namelist;
  else
    work_desc = work_desc || 'unknown author/sender';
  end if;


  ------------------------------
  -- Add origin to description
  ------------------------------
  misc = '';
  select location_name
  into misc
  from cofk_union_location l, cofk_union_relationship r
  where r.left_table_name = 'cofk_union_work'
  and r.left_id_value = input_work_id
  and r.relationship_type = 'was_sent_from'
  and r.right_table_name = 'cofk_union_location'
  and r.right_id_value = l.location_id::varchar;

  if misc > '' then
    work_desc = work_desc || ' (' || misc || ')';
  end if;


  --------------------------------
  -- Add addressees to description
  --------------------------------
  select dbf_cofk_union_list_addressees_searchable as namelist
  into relrec
  from dbf_cofk_union_list_addressees_searchable( workrec.work_id );

  if relrec.namelist > '' then
    work_desc = work_desc || ' to ' || relrec.namelist;
  else
    work_desc = work_desc || ' to unknown addressee';
  end if;


  ---------------------------------
  -- Add destination to description
  ---------------------------------
  misc = '';
  select location_name
  into misc
  from cofk_union_location l, cofk_union_relationship r
  where r.left_table_name = 'cofk_union_work'
  and r.left_id_value = input_work_id
  and r.relationship_type = 'was_sent_to'
  and r.right_table_name = 'cofk_union_location'
  and r.right_id_value = l.location_id::varchar;

  if misc > '' then
    work_desc = work_desc || ' (' || misc || ')';
  end if;

  -----------------------------------------------------------------------
  -- If no date, sender or addressee, see if it is a cross-reference card
  -----------------------------------------------------------------------
  if work_desc = 'Unknown date: unknown author/sender to unknown addressee' then
    select dbf_cofk_union_list_people_mentioned_searchable as namelist
    into relrec
    from dbf_cofk_union_list_people_mentioned_searchable( workrec.work_id );

    if relrec.namelist > '' then
      work_desc = 'Refers to: ' || relrec.namelist;

    else -- see if there are any 'oddments'
      select dbf_cofk_union_list_comments_on_work_searchable as namelist
      into relrec
      from dbf_cofk_union_list_comments_on_work_searchable( workrec.work_id );

      if relrec.namelist > '' then
				relrec.namelist = replace( relrec.namelist, '<lb/>', ' ' );
				relrec.namelist = replace( relrec.namelist, '<p>', ' ' );
				relrec.namelist = replace( relrec.namelist, '</p>', ' ' );
				relrec.namelist = replace( relrec.namelist, carriage_return, ' ' );
				relrec.namelist = replace( relrec.namelist, newline, ' ' );

        if strpos( relrec.namelist, '<' ) > 0 then  -- get rid of other tags now so we don't risk cutting them in half!
          relrec.namelist = substr( relrec.namelist, 1,  strpos( relrec.namelist, '<' ) - 1 );
        end if;

        if length( relrec.namelist ) > max_notes_length then
          relrec.namelist = substr( relrec.namelist, 1, max_notes_length ) || '...';
        end if;

        if relrec.namelist > '' then
          work_desc = 'Notes on index card: ' || relrec.namelist;
        end if;
      end if;

    end if;
  else
    work_desc = replace( work_desc, '<lb/>', ' ' );
    work_desc = replace( work_desc, '<p>', ' ' );
    work_desc = replace( work_desc, '</p>', ' ' );
    work_desc = replace( work_desc, carriage_return, ' ' );
    work_desc = replace( work_desc, newline, ' ' );
  end if;


  return work_desc;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_get_work_desc(input_work_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_link_to_edit_app(character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_link_to_edit_app(link_text character varying, query_string character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_get_constant_value( 'link_text_start_marker' )
      || dbf_cofk_union_get_constant_value( 'href_start_marker' )
      || dbf_cofk_union_get_constant_value('editing_interface_url') 
      || coalesce( query_string, '' )  -- needs '?' as part of value e.g. '?iwork_id=200179'
      || dbf_cofk_union_get_constant_value( 'href_end_marker' )
      || link_text
      || dbf_cofk_union_get_constant_value( 'link_text_end_marker' );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_link_to_edit_app(link_text character varying, query_string character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_addressees(text, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_addressees(work_id_parm text, html_output integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_rels_decoded( 'cofk_union_person', -- required_table
                                            'was_addressed_to',   -- required_reltype 
                                            'cofk_union_work',   -- known_table  
                                            work_id_parm,         -- known_id 
                                            html_output );
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_addressees(work_id_parm text, html_output integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_addressees_for_display(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_addressees_for_display(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin
  return dbf_cofk_union_list_addressees( work_id_parm, 1 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_addressees_for_display(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_addressees_searchable(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_addressees_searchable(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin
  return dbf_cofk_union_list_addressees( work_id_parm, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_addressees_searchable(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_comments_on_author(text, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_comments_on_author(work_id_parm text, html_output integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_rels_decoded( 'cofk_union_comment', -- required_table
                                            'refers_to_author',    -- required_reltype
                                            'cofk_union_work',    -- known_table  
                                            work_id_parm,          -- known_id 
                                            html_output );
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_comments_on_author(work_id_parm text, html_output integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_comments_on_author_for_display(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_comments_on_author_for_display(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_comments_on_author( work_id_parm, 1 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_comments_on_author_for_display(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_comments_on_author_searchable(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_comments_on_author_searchable(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_comments_on_author( work_id_parm, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_comments_on_author_searchable(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_comments_on_work(text, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_comments_on_work(work_id_parm text, html_output integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_rels_decoded( 'cofk_union_comment', -- required_table
                                            'refers_to',           -- required_reltype (any)
                                            'cofk_union_work',    -- known_table  
                                            work_id_parm,          -- known_id 
                                            html_output );
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_comments_on_work(work_id_parm text, html_output integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_comments_on_work_for_display(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_comments_on_work_for_display(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_comments_on_work( work_id_parm, 1 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_comments_on_work_for_display(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_comments_on_work_searchable(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_comments_on_work_searchable(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_comments_on_work( work_id_parm, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_comments_on_work_searchable(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_creators(text, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_creators(work_id_parm text, html_output integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_rels_decoded( 'cofk_union_person', -- required_table
                                            'created',            -- required_reltype 
                                            'cofk_union_work',   -- known_table  
                                            work_id_parm,         -- known_id 
                                            html_output );
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_creators(work_id_parm text, html_output integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_creators_for_display(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_creators_for_display(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_creators( work_id_parm, 1 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_creators_for_display(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_creators_searchable(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_creators_searchable(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_creators( work_id_parm, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_creators_searchable(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_destinations(text, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_destinations(work_id_parm text, html_output integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  expand_details integer;
begin

  -- The 'non HTML' version is meant to be searchable, so include all synonyms for place.
  -- The 'HTML' version is for display so no need to clutter up the display with lots of synonyms.
  -- But on second thoughts, there is enough space on the display to show full details of origin and destination
  if html_output = 1 then
    --expand_details = 0;
    expand_details = 1; -- decided to show full details of origin and destination after all
  else
    expand_details = 1;
  end if;

  return dbf_cofk_union_list_rels_decoded( 'cofk_union_location', -- required_table
                                            'was_sent_to',          -- required_reltype 
                                            'cofk_union_work',     -- known_table  
                                            work_id_parm,           -- known_id 
                                            html_output,
                                            expand_details );
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_destinations(work_id_parm text, html_output integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_destinations_for_display(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_destinations_for_display(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_destinations( work_id_parm, 1 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_destinations_for_display(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_destinations_searchable(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_destinations_searchable(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_destinations( work_id_parm, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_destinations_searchable(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_images_of_entity(character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_images_of_entity(input_tablename character varying, input_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  image_details text;

  filename_separator    constant varchar(3) = ' ~ ';  -- Separator for multiple filenames as passed back
                                                      -- from the 'Rels Decoded' function.

  filename_start_marker constant varchar(30) = 'xxxCofkImageIDStartxxx'; -- Pass filenames back surrounded by these
  filename_end_marker   constant varchar(30) = 'xxxCofkImageIDEndxxx';

begin

  image_details = dbf_cofk_union_list_rels_decoded( 
                    'cofk_union_image',           -- required table
                    'image_of',                   -- required relationship
                    input_tablename,              -- known table 
                    input_id,                     -- known ID
                    0 );                          -- Not HTML output i.e. do not add <ul> and <li> tags

  image_details = coalesce( image_details, '' );
  image_details = trim( image_details );

  if strpos( image_details, filename_separator ) > 0 then
    image_details = replace( image_details, filename_separator, filename_end_marker || ' ' || filename_start_marker );
  end if;

  if image_details > '' then
    image_details = filename_start_marker || image_details || filename_end_marker;
  end if;

  return image_details;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_images_of_entity(input_tablename character varying, input_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_images_of_work(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_images_of_work(input_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  manifestation_id_rec record;
  image_details text not null = '';
begin

  for manifestation_id_rec in 
  select * from dbf_cofk_union_list_rel_ids ( 'cofk_union_manifestation', -- required table
                                               'is_manifestation_of',       -- required relationship
                                               'cofk_union_work',          -- known table 
                                               input_id )                   -- known ID
  loop
    image_details = image_details || ' ' || dbf_cofk_union_list_manifestation_images( manifestation_id_rec.id_value );
    image_details = trim( image_details );
  end loop;

  return image_details;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_images_of_work(input_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_lefthand_rel_ids(character varying, character varying, character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_lefthand_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) RETURNS SETOF public.cofk_union_rel_one_side
    LANGUAGE plpgsql
    AS $$

declare
  one_side cofk_union_rel_one_side%rowtype;
  lookup_row record;
begin

  for lookup_row in select 
                      relationship_id,
                      left_table_name as table_name,
                      left_id_value as id_value,
                      relationship_valid_from as start_date,
                      relationship_valid_till as end_date
                    from 
                      cofk_union_relationship 
                    where 
                      left_table_name like required_table
                      and relationship_type like required_reltype
                      and right_table_name = known_table
                      and right_id_value = known_id

                    order by start_date, end_date, relationship_id
  loop
    one_side.table_name = lookup_row.table_name;
    one_side.id_value   = lookup_row.id_value  ;
    one_side.start_date = lookup_row.start_date;
    one_side.end_date   = lookup_row.end_date;

    return next one_side;
  end loop;

  return;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_lefthand_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_lefthand_rels_decoded(character varying, character varying, character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) RETURNS text
    LANGUAGE plpgsql
    AS $$
begin

  return dbf_cofk_union_list_lefthand_rels_decoded( required_table, required_reltype, known_table, known_id,
                                           html_output, 0 );  -- 0 means 'don't expand details' 
end;
$$;


ALTER FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_lefthand_rels_decoded(character varying, character varying, character varying, character varying, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) RETURNS text
    LANGUAGE plpgsql
    AS $$


declare
  lookup_string text not null default '';
  lookup_row record;
  curr_item integer not null default 0;
begin

  for lookup_row in select * from dbf_cofk_union_list_lefthand_rel_ids (  required_table,
                                                                 required_reltype,
                                                                 known_table, 
                                                                 known_id )
  loop
    curr_item := curr_item + 1;

    lookup_string := dbf_cofk_union_add_to_rels_decoded_string(  lookup_row.table_name,
                                                                 lookup_row.id_value,
                                                                 lookup_row.start_date,
                                                                 lookup_row.end_date,
                                                                 html_output,
                                                                 expand_details,
                                                                 curr_item,
                                                                 lookup_string );
  end loop;

  if html_output = 1 and curr_item > 1 then
    lookup_string := '<ul>' || lookup_string || '</ul>';
  end if;

  return lookup_string;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_lefthand_rels_decoded_for_display(character varying, character varying, character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_lefthand_rels_decoded( required_table, required_reltype, known_table, known_id, 1 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_lefthand_rels_decoded_searchable(character varying, character varying, character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_lefthand_rels_decoded( required_table, required_reltype, known_table, known_id, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_manifestation_images(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_manifestation_images(input_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin
  return dbf_cofk_union_list_images_of_entity( 'cofk_union_manifestation', input_id );
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_manifestation_images(input_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_manifestations(text, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_manifestations(work_id_parm text, html_output integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_rels_decoded( 'cofk_union_manifestation', -- required_table
                                            'is_manifestation_of',       -- required_reltype 
                                            'cofk_union_work',          -- known_table  
                                            work_id_parm,                -- known_id 
                                            html_output );
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_manifestations(work_id_parm text, html_output integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_manifestations_for_display(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_manifestations_for_display(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_manifestations( work_id_parm, 1 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_manifestations_for_display(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_manifestations_searchable(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_manifestations_searchable(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_manifestations( work_id_parm, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_manifestations_searchable(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_origins(text, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_origins(work_id_parm text, html_output integer) RETURNS text
    LANGUAGE plpgsql
    AS $$


declare
  expand_details integer;
begin

  -- The 'non HTML' version is meant to be searchable, so include all synonyms for place.
  -- The 'HTML' version is for display so no need to clutter up the display with lots of synonyms.
  -- But on second thoughts, there is enough space on the display to show full details of origin and destination
  if html_output = 1 then
    expand_details = 0;
    expand_details = 1; -- decided to show full details of origin and destination after all
  else
    expand_details = 1;
  end if;

  return dbf_cofk_union_list_rels_decoded( 'cofk_union_location', -- required_table
                                            'was_sent_from',        -- required_reltype 
                                            'cofk_union_work',     -- known_table  
                                            work_id_parm,           -- known_id 
                                            html_output,
                                            expand_details );
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_origins(work_id_parm text, html_output integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_origins_for_display(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_origins_for_display(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_origins( work_id_parm, 1 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_origins_for_display(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_origins_searchable(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_origins_searchable(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_origins( work_id_parm, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_origins_searchable(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_people_mentioned(text, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_people_mentioned(work_id_parm text, html_output integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_rels_decoded( 'cofk_union_person', -- required_table
                                            'mentions',           -- required_reltype 
                                            'cofk_union_work',   -- known_table  
                                            work_id_parm,         -- known_id 
                                            html_output );
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_people_mentioned(work_id_parm text, html_output integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_people_mentioned_for_display(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_people_mentioned_for_display(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_people_mentioned( work_id_parm, 1 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_people_mentioned_for_display(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_people_mentioned_searchable(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_people_mentioned_searchable(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_people_mentioned( work_id_parm, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_people_mentioned_searchable(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_rel_ids(character varying, character varying, character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) RETURNS SETOF public.cofk_union_rel_one_side
    LANGUAGE plpgsql
    AS $$

declare
  one_side cofk_union_rel_one_side%rowtype;
  lookup_row record;
begin

  for lookup_row in select 
                      relationship_id,
                      left_table_name as table_name,
                      left_id_value as id_value,
                      relationship_valid_from as start_date,
                      relationship_valid_till as end_date
                    from 
                      cofk_union_relationship 
                    where 
                      left_table_name like required_table
                      and relationship_type like required_reltype
                      and right_table_name = known_table
                      and right_id_value = known_id

                    union select 

                      relationship_id,
                      right_table_name as table_name,
                      right_id_value as id_value,
                      relationship_valid_from as start_date,
                      relationship_valid_till as end_date
                    from 
                      cofk_union_relationship 
                    where 
                      right_table_name like required_table
                      and relationship_type like required_reltype
                      and left_table_name = known_table
                      and left_id_value = known_id

                    order by start_date, end_date, relationship_id
  loop
    one_side.table_name = lookup_row.table_name;
    one_side.id_value   = lookup_row.id_value  ;
    one_side.start_date = lookup_row.start_date;
    one_side.end_date   = lookup_row.end_date;

    return next one_side;
  end loop;

  return;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_related_resources(character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_related_resources(input_table character varying, input_id character varying, full_details integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  resource_id_rec record;
  resource_details_rec record;
  resource_summary text not null default '';

  link_text_start_marker constant varchar(20) := 'xxxCofkLinkStartxxx';
  link_text_end_marker   constant varchar(20) := 'xxxCofkLinkEndxxx';
  href_start_marker      constant varchar(20) := 'xxxCofkHrefStartxxx';
  href_end_marker        constant varchar(20) := 'xxxCofkHrefEndxxx';

  newline constant varchar(1) := E'\n';
begin

  for resource_id_rec in 
  select * from dbf_cofk_union_list_rel_ids ( 'cofk_union_resource', -- required table
                                               '%',                    -- required relationship, i.e. any
                                               input_table,            -- known table 
                                               input_id )              -- known ID
  loop
    if resource_summary > '' then
      resource_summary := resource_summary || ' ~ ';
    end if;

    -- Get resource description and URL
    select * into resource_details_rec
    from cofk_union_resource
    where resource_id =  resource_id_rec.id_value::integer;

    if trim( resource_details_rec.resource_url ) > '' then
      resource_summary := resource_summary || link_text_start_marker  -- will be converted to <a
                                           || href_start_marker       -- will be converted to href="
                                           || resource_details_rec.resource_url
                                           || href_end_marker         -- will be converted to ">
                                           || resource_details_rec.resource_name
                                           || link_text_end_marker;   -- will be converted to </a>
    else
      resource_summary := resource_summary || resource_details_rec.resource_name;
    end if;

    if full_details > 0 and resource_details_rec.resource_details > '' then
      resource_summary := resource_summary || newline || resource_details_rec.resource_details;
    end if;
  end loop;

  return resource_summary;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_related_resources(input_table character varying, input_id character varying, full_details integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_rels_decoded(character varying, character varying, character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) RETURNS text
    LANGUAGE plpgsql
    AS $$
begin

  return dbf_cofk_union_list_rels_decoded( required_table, required_reltype, known_table, known_id,
                                           html_output, 0 );  -- 0 means 'don't expand details' 
end;
$$;


ALTER FUNCTION public.dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_rels_decoded(character varying, character varying, character varying, character varying, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) RETURNS text
    LANGUAGE plpgsql
    AS $$


declare
  lookup_string text not null default '';
  lookup_row record;
  curr_item integer not null default 0;
begin

  for lookup_row in select * from dbf_cofk_union_list_rel_ids (  required_table,
                                                                 required_reltype,
                                                                 known_table, 
                                                                 known_id )
  loop
    curr_item := curr_item + 1;

    lookup_string := dbf_cofk_union_add_to_rels_decoded_string(  lookup_row.table_name,
                                                                 lookup_row.id_value,
                                                                 lookup_row.start_date,
                                                                 lookup_row.end_date,
                                                                 html_output,
                                                                 expand_details,
                                                                 curr_item,
                                                                 lookup_string );
  end loop;

  if html_output = 1 and curr_item > 1 then
    lookup_string := '<ul>' || lookup_string || '</ul>';
  end if;

  return lookup_string;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_rels_decoded_for_display(character varying, character varying, character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_rels_decoded( required_table, required_reltype, known_table, known_id, 1 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_rels_decoded_searchable(character varying, character varying, character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_rels_decoded( required_table, required_reltype, known_table, known_id, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_righthand_rel_ids(character varying, character varying, character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_righthand_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) RETURNS SETOF public.cofk_union_rel_one_side
    LANGUAGE plpgsql
    AS $$

declare
  one_side cofk_union_rel_one_side%rowtype;
  lookup_row record;
begin

  for lookup_row in select 
                      relationship_id,
                      right_table_name as table_name,
                      right_id_value as id_value,
                      relationship_valid_from as start_date,
                      relationship_valid_till as end_date
                    from 
                      cofk_union_relationship 
                    where 
                      right_table_name like required_table
                      and relationship_type like required_reltype
                      and left_table_name = known_table
                      and left_id_value = known_id

                    order by start_date, end_date, relationship_id
  loop
    one_side.table_name = lookup_row.table_name;
    one_side.id_value   = lookup_row.id_value  ;
    one_side.start_date = lookup_row.start_date;
    one_side.end_date   = lookup_row.end_date;

    return next one_side;
  end loop;

  return;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_righthand_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_righthand_rels_decoded(character varying, character varying, character varying, character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) RETURNS text
    LANGUAGE plpgsql
    AS $$
begin

  return dbf_cofk_union_list_righthand_rels_decoded( required_table, required_reltype, known_table, known_id,
                                           html_output, 0 );  -- 0 means 'don't expand details' 
end;
$$;


ALTER FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_righthand_rels_decoded(character varying, character varying, character varying, character varying, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) RETURNS text
    LANGUAGE plpgsql
    AS $$


declare
  lookup_string text not null default '';
  lookup_row record;
  curr_item integer not null default 0;
begin

  for lookup_row in select * from dbf_cofk_union_list_righthand_rel_ids (  required_table,
                                                                 required_reltype,
                                                                 known_table, 
                                                                 known_id )
  loop
    curr_item := curr_item + 1;

    lookup_string := dbf_cofk_union_add_to_rels_decoded_string(  lookup_row.table_name,
                                                                 lookup_row.id_value,
                                                                 lookup_row.start_date,
                                                                 lookup_row.end_date,
                                                                 html_output,
                                                                 expand_details,
                                                                 curr_item,
                                                                 lookup_string );
  end loop;

  if html_output = 1 and curr_item > 1 then
    lookup_string := '<ul>' || lookup_string || '</ul>';
  end if;

  return lookup_string;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_righthand_rels_decoded_for_display(character varying, character varying, character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_righthand_rels_decoded( required_table, required_reltype, known_table, known_id, 1 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_righthand_rels_decoded_searchable(character varying, character varying, character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_list_righthand_rels_decoded( required_table, required_reltype, known_table, known_id, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_subjects_of_work(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_subjects_of_work(work_id_parm text) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  subject_list text;
begin

  subject_list = dbf_cofk_union_list_rels_decoded( 'cofk_union_subject', -- required_table
                                                   'deals_with',         -- required_reltype (any)
                                                   'cofk_union_work',    -- known_table  
                                                   work_id_parm,         -- known_id 
                                                   0 );
  subject_list = replace( subject_list, ' ~ ', ', ' );
  return subject_list;
end;


$$;


ALTER FUNCTION public.dbf_cofk_union_list_subjects_of_work(work_id_parm text) OWNER TO postgres;

--
-- Name: dbf_cofk_union_list_work_resources_brief(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_list_work_resources_brief(input_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin
  return dbf_cofk_union_list_related_resources( 'cofk_union_work', input_id, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_list_work_resources_brief(input_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_person_summary(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_person_summary(input_person_id integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_person_summary( input_person_id, 0 ); 
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_person_summary(input_person_id integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_person_summary(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$
begin

  return dbf_cofk_union_person_summary( input_person_id, 0 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_person_summary(integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_person_summary(input_person_id integer, include_works integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  text_person_id varchar(100);
begin

  select person_id into text_person_id
  from cofk_union_person
  where iperson_id = input_person_id;

  if text_person_id > '' then
    return dbf_cofk_union_person_summary( text_person_id, include_works ); 
  else
    return '';
  end if;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_person_summary(input_person_id integer, include_works integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_person_summary(character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying, include_works integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_person_summary( input_person_id, include_works, 0 ); -- 0 here means 'do not expand details'
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying, include_works integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_person_summary(character varying, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying, include_works integer, expand_details integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

declare
  summary text = '';
  decode text = '';
  newline constant varchar(1) = E'\n';
  relrow record;
  other_table varchar(100) not null = '';
  other_id_value varchar(100) not null = '';
  last_reltype varchar(50) not null = '';
  reldesc varchar(200) not null = '';
  first_char varchar(1) not null = '';
  has_resources varchar(1) not null = 'N';
  start_date varchar(4);
  end_date varchar(4);
begin


  for relrow in 
    select rel.*, typ.desc_left_to_right as type_desc
    from 
      cofk_union_relationship rel, cofk_union_relationship_type typ
    where 
      rel.left_table_name = 'cofk_union_person'
      and rel.left_id_value = input_person_id
    and
      rel.relationship_type = typ.relationship_code

    union select rel.*, typ.desc_right_to_left as type_desc
    from 
      cofk_union_relationship rel, cofk_union_relationship_type typ
    where 
      rel.right_table_name = 'cofk_union_person'
      and rel.right_id_value = input_person_id
    and
      rel.relationship_type = typ.relationship_code

    order by 
      type_desc, relationship_valid_from, relationship_valid_till, relationship_id
  loop
    if relrow.left_table_name = 'cofk_union_person' and relrow.left_id_value = input_person_id then
      other_table = relrow.right_table_name;
      other_id_value = relrow.right_id_value;
    else
      other_table = relrow.left_table_name;
      other_id_value = relrow.left_id_value;
    end if;

    if include_works = 0 and other_table = 'cofk_union_work' then
      continue;
    elsif other_table = 'cofk_union_resource' then
      has_resources = 'Y';
      continue; -- do related resources later
    elsif other_table = 'cofk_union_image' then
      continue; -- put images in a separate column
    end if;

    reldesc = lower( relrow.type_desc );
    first_char = substr( reldesc, 1, 1 );
    first_char = upper( first_char );
    reldesc = first_char || substr( reldesc, 2 );

    if other_table = 'cofk_union_role_category' and reldesc = 'Member of' then
      continue;  -- have now decided to include these details in 'Roles/titles' not 'Other details summary'

      -- The following line will never now be reached. But may as well leave it in, in case I change my mind.
      reldesc = 'Professional categories'; -- just make the decode a bit more suitable
    end if;

    if summary > '' then
      summary = summary || newline;
    end if;

    if reldesc != last_reltype then

      if summary > '' then
        summary = summary || newline;
      end if;

      summary = summary || '*';

      summary = summary || reldesc || ':' || newline;
    end if;
    summary = summary || ' ~';

    decode = dbf_cofk_union_decode( other_table, 
                                    other_id_value, 
                                    0,  -- don't suppress links
                                    expand_details ); 

    if relrow.relationship_valid_from is not null and relrow.relationship_valid_till is not null then
      start_date = to_char( relrow.relationship_valid_from, 'YYYY' );
      end_date = to_char( relrow.relationship_valid_till, 'YYYY' );
      if start_date = end_date then
        decode = start_date || ': ' || decode;
      else
        decode = start_date || '-' || end_date || ': ' || decode;
      end if;

    elsif relrow.relationship_valid_from is not null then
      start_date = to_char( relrow.relationship_valid_from, 'YYYY' );
      decode = 'From ' || start_date || ': ' || decode;

    elsif relrow.relationship_valid_till is not null then
      end_date = to_char( relrow.relationship_valid_till, 'YYYY' );
      decode = 'To ' || end_date || ': ' || decode;
    end if;

    summary = summary || decode;

    last_reltype = reldesc;
  end loop;

  if has_resources = 'Y' then -- provide the version with a link to the URL
    decode = dbf_cofk_union_list_related_resources( 'cofk_union_person', 
                                                    input_person_id, 
                                                    expand_details ); -- include/exclude further details of resource

    if summary > '' then
      summary = summary || newline || newline || '* ';
    end if;
    summary = summary || 'Related resources: ' || decode;
  end if;

  return summary;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying, include_works integer, expand_details integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_person_summary_with_works(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_person_summary_with_works(input_person_id integer) RETURNS text
    LANGUAGE plpgsql
    AS $$

begin

  return dbf_cofk_union_person_summary( input_person_id, 1 ); 
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_person_summary_with_works(input_person_id integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_person_summary_with_works(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_person_summary_with_works(input_person_id character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$
begin

  return dbf_cofk_union_person_summary( input_person_id, 1 );
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_person_summary_with_works(input_person_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_primary_key(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_primary_key(the_table_name character varying) RETURNS character varying
    LANGUAGE plpgsql
    AS $$

declare
  collection_prefix constant varchar(12) = 'cofk_union_';
  prefix_length smallint;
  key_name varchar(100);
begin

    key_name = '';

    if the_table_name = collection_prefix || 'queryable_work' then
      key_name = 'work_id';

    else
      prefix_length = length( collection_prefix );
      key_name = substr( the_table_name, prefix_length+1 );
      key_name = key_name || '_id';
    end if;

    return key_name;
end;
$$;


ALTER FUNCTION public.dbf_cofk_union_primary_key(the_table_name character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_refresh_language_of_manifestation(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_refresh_language_of_manifestation() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

declare
  input_manifestation_id varchar(100) not null = '';
  old_lang text;
  new_lang text;
begin

  if TG_OP = 'DELETE' then
    input_manifestation_id = old.manifestation_id;
  else
    input_manifestation_id = new.manifestation_id;
  end if;

  select language_of_manifestation into old_lang from cofk_union_manifestation where manifestation_id = input_manifestation_id;

  select dbf_cofk_union_get_language_of_manifestation( input_manifestation_id ) into new_lang;

  if coalesce( old_lang, '' ) != coalesce( new_lang, '' ) then
    update cofk_union_manifestation
    set language_of_manifestation = new_lang
    where manifestation_id = input_manifestation_id;
  end if;

  if TG_OP = 'DELETE' then
    return old;
  else
    return new;
  end if;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_refresh_language_of_manifestation() OWNER TO postgres;

--
-- Name: dbf_cofk_union_refresh_language_of_work(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_refresh_language_of_work() RETURNS trigger
    LANGUAGE plpgsql
    AS $$

declare
  input_work_id varchar(100) not null = '';
  old_lang text;
  new_lang text;
begin

  if TG_OP = 'DELETE' then
    input_work_id = old.work_id;
  else
    input_work_id = new.work_id;
  end if;

  select language_of_work into old_lang from cofk_union_work where work_id = input_work_id;

  select dbf_cofk_union_get_language_of_work( input_work_id ) into new_lang;

  if coalesce( old_lang, '' ) != coalesce( new_lang, '' ) then
    update cofk_union_work
    set language_of_work = new_lang
    where work_id = input_work_id;
  end if;

  if TG_OP = 'DELETE' then
    return old;
  else
    return new;
  end if;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_refresh_language_of_work() OWNER TO postgres;

--
-- Name: dbf_cofk_union_refresh_person_summary(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_refresh_person_summary(input_id integer) RETURNS void
    LANGUAGE plpgsql
    AS $$

declare
  text_id varchar(100);
begin

  select person_id into text_id
  from cofk_union_person
  where iperson_id = input_id;

  perform dbf_cofk_union_refresh_person_summary( text_id, input_id );
  return;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_refresh_person_summary(input_id integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_refresh_person_summary(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_refresh_person_summary(input_id character varying) RETURNS void
    LANGUAGE plpgsql
    AS $$

declare
  integer_id integer;
begin

  select iperson_id into integer_id
  from cofk_union_person
  where person_id = input_id;

  perform dbf_cofk_union_refresh_person_summary( input_id, integer_id );
  return;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_refresh_person_summary(input_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_refresh_person_summary(character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_refresh_person_summary(input_text_id character varying, input_integer_id integer) RETURNS void
    LANGUAGE plpgsql
    AS $$

declare
  oldrec record;
  new_short_string text;
  new_long_string text;
  new_sent integer := 0;
  new_recd integer := 0;
  new_all_works integer := 0;
  new_mentioned integer;
  new_author_of integer;
  has_changed varchar(1) not null := 'N';
begin

  select * into oldrec
  from cofk_union_person_summary
  where iperson_id = input_integer_id;

  select dbf_cofk_union_person_summary( input_text_id,
                                        0, -- don't include works
                                        0  -- don't expand details
                                      ),

         dbf_cofk_union_person_summary( input_text_id,
                                        0, -- don't include works
                                        1  -- do expand details
                                      )
  into new_short_string, new_long_string;

  -- this 'if' is now unnecessary as IMPAcT has its own separate version of the function these days
  if dbf_cofk_union_get_constant_value( 'system_prefix' ) = 'cofk' then
    select coalesce( count(*), 0) 
    into new_sent
    from cofk_union_person_sent_view vs 
    where vs.person_id = input_text_id;

    select coalesce( count(*), 0) 
    into new_recd
    from cofk_union_person_recd_view vr 
    where vr.person_id = input_text_id;

    select coalesce( count(*), 0) 
    into new_all_works
    from cofk_union_person_all_works_view va 
    where va.person_id = input_text_id;
  end if;

  select coalesce( count(*), 0) 
  into new_mentioned
  from cofk_union_person_mentioned_view vm 
  where vm.person_id = input_text_id;

  if coalesce( new_long_string, '' ) != coalesce( oldrec.other_details_summary_searchable, '' ) 
  or coalesce( new_short_string, '' ) != coalesce( oldrec.other_details_summary, '' ) 
  or oldrec.sent != new_sent
  or oldrec.recd != new_recd
  or oldrec.all_works != new_all_works
  or oldrec.mentioned != new_mentioned
  then
    has_changed = 'Y';
  end if;

  if has_changed = 'Y' then
    update cofk_union_person_summary 
    set other_details_summary = new_short_string,
        other_details_summary_searchable = new_long_string,
        sent = new_sent,
        recd = new_recd,
        all_works = new_all_works,
        mentioned = new_mentioned
    where iperson_id = input_integer_id;
  end if;

  ----------------------
  -- Add role categories
  ----------------------
  new_long_string = dbf_cofk_union_list_rels_decoded( 'cofk_union_role_category', -- required_table
                                                'member_of',     -- required_reltype 
                                                'cofk_union_person',   -- known_table 
                                                input_text_id,   -- known_id 
                                                1,               -- html_output 
                                                1 );             -- expand_details 
  update cofk_union_person_summary 
  set role_categories = coalesce( new_long_string, '' )
  where iperson_id = input_integer_id;

  -------------
  -- Add images
  -------------
  new_long_string = dbf_cofk_union_list_images_of_entity( 'cofk_union_person', input_text_id );
  update cofk_union_person_summary 
  set images = coalesce( new_long_string, '' )
  where iperson_id = input_integer_id;

  return;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_refresh_person_summary(input_text_id character varying, input_integer_id integer) OWNER TO postgres;

--
-- Name: dbf_cofk_union_refresh_queryable_work(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_refresh_queryable_work(input_work_id character varying) RETURNS void
    LANGUAGE plpgsql
    AS $$
  declare
  workrec record;
  new_flags text not null := '';
  misc text;
  rel_rec record;
  manifestation_id_var varchar(100);
  newline constant varchar(1) = E'\n';
begin

  -----------------------------------------------------------
  -- Refresh columns copied from the main 'work' table itself
  -----------------------------------------------------------
  select * from cofk_union_work into workrec where work_id = input_work_id;

  if workrec.keywords > '' then
    workrec.keywords = 'Keywords: ' || workrec.keywords;
  end if;

  update cofk_union_queryable_work set

    description                =    workrec.description                ,

    date_of_work_as_marked     =    workrec.date_of_work_as_marked     ,
    date_of_work_std           =    workrec.date_of_work_std::date     ,
    date_of_work_inferred      =    workrec.date_of_work_inferred      ,
    date_of_work_uncertain     =    workrec.date_of_work_uncertain     ,
    date_of_work_approx        =    workrec.date_of_work_approx        ,

    authors_as_marked          =    workrec.authors_as_marked          ,
    authors_inferred           =    workrec.authors_inferred           ,
    authors_uncertain          =    workrec.authors_uncertain          ,

    addressees_as_marked       =    workrec.addressees_as_marked       ,
    addressees_inferred        =    workrec.addressees_inferred        ,
    addressees_uncertain       =    workrec.addressees_uncertain       ,

    origin_as_marked           =    workrec.origin_as_marked           ,
    origin_inferred            =    workrec.origin_inferred            ,
    origin_uncertain           =    workrec.origin_uncertain           ,

    destination_as_marked      =    workrec.destination_as_marked      ,
    destination_inferred       =    workrec.destination_inferred       ,
    destination_uncertain      =    workrec.destination_uncertain      ,

    keywords                   =    workrec.keywords                   ,
    language_of_work           =    workrec.language_of_work           ,
    abstract                   =    workrec.abstract                   ,

    work_is_translation        =    workrec.work_is_translation        ,

    accession_code             =    workrec.accession_code             ,
    original_catalogue         =    workrec.original_catalogue         ,
    work_to_be_deleted         =    workrec.work_to_be_deleted         ,
    editors_notes              =    workrec.editors_notes              ,
    relevant_to_cofk           =    workrec.relevant_to_cofk           ,
    edit_status                =    workrec.edit_status
  where
    work_id = input_work_id;


  -- Year of work copied from main 'work' table
  update cofk_union_queryable_work set date_of_work_std_year =
    case substr( workrec.date_of_work_std, 1, 4 )  
      when '9999' then null else substr( workrec.date_of_work_std, 1, 4 )::integer
    end
  where work_id = input_work_id;

  -- Month of work copied from main 'work' table
  update cofk_union_queryable_work set date_of_work_std_month =
    case 
      when workrec.date_of_work_std_is_range > 0 and ( workrec.date_of_work2_std_year is not null or
                                                       workrec.date_of_work2_std_month is not null or
                                                       workrec.date_of_work2_std_day is not null ) 
      then
        workrec.date_of_work2_std_month
      else
        workrec.date_of_work_std_month
    end
  where work_id = input_work_id;

  -- Day of work copied from main 'work' table
  update cofk_union_queryable_work set date_of_work_std_day =
    case 
      when workrec.date_of_work_std_is_range > 0 and ( workrec.date_of_work2_std_year is not null or
                                                       workrec.date_of_work2_std_month is not null or
                                                       workrec.date_of_work2_std_day is not null ) 
      then
        workrec.date_of_work2_std_day
      else
        workrec.date_of_work_std_day
    end
  where work_id = input_work_id;

  ------------------------------------
  -- Summarise relationships with work
  ------------------------------------

  -- Creators
  update cofk_union_queryable_work 
  set creators_searchable = dbf_cofk_union_decode_work_role_with_aliases( 'created', input_work_id )
  where work_id = input_work_id;

  update cofk_union_queryable_work 
  set creators_for_display = dbf_cofk_union_list_creators_for_display( input_work_id )
  where work_id = input_work_id;

  -- Addressees
  update cofk_union_queryable_work 
  set addressees_searchable = dbf_cofk_union_decode_work_role_with_aliases( 'was_addressed_to', input_work_id )
  where work_id = input_work_id;

  update cofk_union_queryable_work 
  set addressees_for_display = dbf_cofk_union_list_addressees_for_display( input_work_id )
  where work_id = input_work_id;


  -- Origins
  update cofk_union_queryable_work 
  set places_from_searchable = dbf_cofk_union_list_origins_searchable( input_work_id )
  where work_id = input_work_id;

  update cofk_union_queryable_work 
  set places_from_for_display = dbf_cofk_union_list_origins_for_display( input_work_id )
  where work_id = input_work_id;


  -- Destinations
  update cofk_union_queryable_work 
  set places_to_searchable = dbf_cofk_union_list_destinations_searchable( input_work_id )
  where work_id = input_work_id;

  update cofk_union_queryable_work 
  set places_to_for_display = dbf_cofk_union_list_destinations_for_display( input_work_id )
  where work_id = input_work_id;


  -- People mentioned
  update cofk_union_queryable_work 
  set people_mentioned = dbf_cofk_union_decode_work_role_with_aliases( 'mentions', input_work_id )
  where work_id = input_work_id;

  -- Places and works mentioned.
  -- These will have to go in with keywords because I haven't got time to add new columns now.

  misc = '';

  select dbf_cofk_union_list_rels_decoded( 'cofk_union_location', --required_table
                                           'mentions_place',      --required_reltype
                                           'cofk_union_work',     --known_table
                                           input_work_id,         --known_id
                                           0                      --html_output 
                                         ) into misc;
  if misc > '' then
    update cofk_union_queryable_work set
      keywords = 'Mentions place: ' || misc
                 || case when keywords > '' then newline || ' ~ ' else '' end 
                 || coalesce( keywords, '' )
    where
      work_id = input_work_id;
  end if;

  misc = '';

  -- This work mentions another?
  for rel_rec in select * from 
  dbf_cofk_union_list_righthand_rel_ids (  'cofk_union_work', --required table
                                           'mentions_work',   --required_reltype
                                           'cofk_union_work', --known_table
                                           input_work_id      --known_id
                                        )
  loop
    select dbf_cofk_union_decode( rel_rec.table_name, rel_rec.id_value ) into misc;

    update cofk_union_queryable_work set
      keywords = 'Mentions work: ' || misc
                 || case when keywords > '' then newline || ' ~ ' else '' end 
                 || coalesce( keywords, '' )
    where
      work_id = input_work_id;
  end loop;

  -- This work is mentioned in another?
  for rel_rec in select * from 
  dbf_cofk_union_list_lefthand_rel_ids (  'cofk_union_work', --required table
                                          'mentions_work',   --required_reltype
                                          'cofk_union_work', --known_table
                                          input_work_id      --known_id
                                       )
  loop
    select dbf_cofk_union_decode( rel_rec.table_name, rel_rec.id_value ) into misc;

    update cofk_union_queryable_work set
      keywords = 'Mentioned in work: ' || misc
                 || case when keywords > '' then newline || ' ~ ' else '' end 
                 || coalesce( keywords, '' )
    where
      work_id = input_work_id;
  end loop;


  -- Shelfmarks/manifestations
  update cofk_union_queryable_work 
  set manifestations_searchable = dbf_cofk_union_full_manifestation_details( input_work_id )
  where work_id = input_work_id;

  update cofk_union_queryable_work 
  set manifestations_for_display = replace( manifestations_searchable, ' -- ', '</li><li>' )
  where work_id = input_work_id;

  update cofk_union_queryable_work 
  set manifestations_for_display = '<ul><li>' || manifestations_for_display || '</li></ul>' 
  where manifestations_for_display like '%</li><li>%' 
  and work_id = input_work_id;

  -- Document type (only expecting one for Selden End cards)
  if workrec.original_catalogue = 'cardindex' then
    select id_value into manifestation_id_var
    from dbf_cofk_union_list_rel_ids( 'cofk_union_manifestation', -- required_table
                                       'is_manifestation_of',       -- required_reltype 
                                       'cofk_union_work',          -- known_table  
                                       input_work_id                -- known_id 
                                     );

    update cofk_union_queryable_work
    set manifestation_type = ( select d.document_type_desc
                               from cofk_union_manifestation m, cofk_lookup_document_type d
                               where m.manifestation_type = d.document_type_code
                               and m.manifestation_id = manifestation_id_var )
    where work_id = input_work_id;
  end if;

  -- Notes on work
  update cofk_union_queryable_work 
  set general_notes = dbf_cofk_union_list_comments_on_work_searchable( input_work_id )
  where work_id = input_work_id;

  -- Notes on authors/senders
  update cofk_union_queryable_work 
  set notes_on_authors = dbf_cofk_union_list_comments_on_author_searchable( input_work_id )
  where work_id = input_work_id;

  if workrec.original_catalogue = 'cardindex' then
    update cofk_union_queryable_work 
    set original_notes = dbf_cofk_union_list_comments_on_work_searchable( input_work_id )
    where work_id = input_work_id;
  end if;

  -------------------------------------
  -- Add expanded display of flags 
  -------------------------------------
  if workrec.date_of_work_inferred > 0 or workrec.date_of_work_uncertain > 0 or workrec.date_of_work_approx > 0 
  then
    if workrec.date_of_work_inferred > 0 then
      new_flags := new_flags || 'Date of work INFERRED. ';
    end if;
    if workrec.date_of_work_uncertain > 0 then
      new_flags := new_flags || 'Date of work UNCERTAIN. ';
    end if;
    if workrec.date_of_work_approx > 0 then
      new_flags := new_flags || 'Date of work APPROXIMATE. ';
    end if;
    if workrec.date_of_work_as_marked > '' then
      new_flags := new_flags || '(Date of work as marked: ' || workrec.date_of_work_as_marked || ') ';
    end if;
  end if;

  if workrec.authors_inferred > 0 or workrec.authors_uncertain > 0 then
    if new_flags > '' then
      new_flags := new_flags || ' ~ ';
    end if;

    if workrec.authors_inferred > 0 then
      new_flags := new_flags || 'Author/sender INFERRED. ';
    end if;
    if workrec.authors_uncertain > 0 then
      new_flags := new_flags || 'Author/sender UNCERTAIN. ';
    end if;

    if workrec.authors_as_marked > '' then
      new_flags := new_flags || '(Author/sender as marked: ' || workrec.authors_as_marked || ')';
    end if;
  end if;

  if workrec.addressees_inferred > 0 or workrec.addressees_uncertain > 0 then
    if new_flags > '' then
      new_flags := new_flags || ' ~ ';
    end if;

    if workrec.addressees_inferred > 0 then
      new_flags := new_flags || 'Addressee INFERRED. ';
    end if;
    if workrec.addressees_uncertain > 0 then
      new_flags := new_flags || 'Addressee UNCERTAIN. ';
    end if;

    if workrec.addressees_as_marked > '' then
      new_flags := new_flags || '(Addressee as marked: ' || workrec.addressees_as_marked || ')';
    end if;
  end if;

  if workrec.origin_inferred > 0 or workrec.origin_uncertain > 0 then
    if new_flags > '' then
      new_flags := new_flags || ' ~ ';
    end if;

    if workrec.origin_inferred > 0 then
      new_flags := new_flags || 'Origin INFERRED. ';
    end if;
    if workrec.origin_uncertain > 0 then
      new_flags := new_flags || 'Origin UNCERTAIN. ';
    end if;

    if workrec.origin_as_marked > '' then
      new_flags := new_flags || '(Origin as marked: ' || workrec.origin_as_marked || ')';
    end if;
  end if;

  if workrec.destination_inferred > 0 or workrec.destination_uncertain > 0 then
    if new_flags > '' then
      new_flags := new_flags || ' ~ ';
    end if;

    if workrec.destination_inferred > 0 then
      new_flags := new_flags || 'Destination INFERRED. ';
    end if;
    if workrec.destination_uncertain > 0 then
      new_flags := new_flags || 'Destination UNCERTAIN. ';
    end if;

    if workrec.destination_as_marked > '' then
      new_flags := new_flags || '(Destination as marked: ' || workrec.destination_as_marked || ')';
    end if;
  end if;

  update cofk_union_queryable_work 
  set flags = new_flags
  where coalesce( flags, '' ) != new_flags
  and work_id = input_work_id;

  ---------------------------------
  -- Add links to related resources
  ---------------------------------
  update cofk_union_queryable_work 
  set related_resources = dbf_cofk_union_list_work_resources_brief( input_work_id )
  where work_id = input_work_id;

  -- Include 'reply to' with related resources
  -- Newer letter (left) is reply to older letter (right)
  misc = '';

  -----------
  -- Reply to
  -----------
  for rel_rec in 
  select * from dbf_cofk_union_list_righthand_rel_ids ( 'cofk_union_work', -- required table
                                                        'is_reply_to',     -- required relationship
                                                        'cofk_union_work', -- known table 
                                                        input_work_id )    -- known ID
  loop
    select dbf_cofk_union_decode( rel_rec.table_name, rel_rec.id_value ) into misc;

    update cofk_union_queryable_work set
      related_resources = 'Reply to: ' || misc
                        || case when related_resources > '' then newline || ' ~ ' else '' end 
                        || coalesce( related_resources, '' )
    where
      work_id = input_work_id;
  end loop;

  --------------
  -- Answered by
  --------------
  for rel_rec in 
  select * from dbf_cofk_union_list_lefthand_rel_ids ( 'cofk_union_work', -- required table
                                                       'is_reply_to',     -- required relationship
                                                       'cofk_union_work', -- known table 
                                                       input_work_id )    -- known ID
  loop
    select dbf_cofk_union_decode( rel_rec.table_name, rel_rec.id_value ) into misc;

    update cofk_union_queryable_work set
      related_resources = 'Answered by: ' || misc
                        || case when related_resources > '' then newline || ' ~ ' else '' end 
                        || coalesce( related_resources, '' )
    where
      work_id = input_work_id;
  end loop;


    --------------------
    -- work A Matches work B
    --------------------
    for rel_rec in
    select * from dbf_cofk_union_list_righthand_rel_ids ( 'cofk_union_work', -- required table
                                                          'matches',     -- required relationship
                                                          'cofk_union_work', -- known table 
                                                          input_work_id )    -- known ID
    loop
      select dbf_cofk_union_decode( rel_rec.table_name, rel_rec.id_value ) into misc;

      update cofk_union_queryable_work set
        related_resources = 'Matches: ' || misc
                            || case when related_resources > '' then newline || ' ~ ' else '' end
                            || coalesce( related_resources, '' )
      where
        work_id = input_work_id;
    end loop;


    --------------------
    -- work B Matches work A
    --------------------
    for rel_rec in
    select * from dbf_cofk_union_list_lefthand_rel_ids ( 'cofk_union_work', -- required table
                                                         'matches',     -- required relationship
                                                         'cofk_union_work', -- known table 
                                                         input_work_id )    -- known ID
    loop
      select dbf_cofk_union_decode( rel_rec.table_name, rel_rec.id_value ) into misc;

      update cofk_union_queryable_work set
        related_resources = 'Matches: ' || misc
                            || case when related_resources > '' then newline || ' ~ ' else '' end
                            || coalesce( related_resources, '' )
      where
        work_id = input_work_id;
    end loop;


    ---------------------------------------
    -- Add links to images of manifestation
    ---------------------------------------
  update cofk_union_queryable_work 
  set images = dbf_cofk_union_list_images_of_work( input_work_id )
  where work_id = input_work_id;


  -----------------------
  -- Add subjects of work
  -----------------------
  update cofk_union_queryable_work 
  set subjects = dbf_cofk_union_list_subjects_of_work( input_work_id )
  where work_id = input_work_id;

  return;
end;
$$;


ALTER FUNCTION public.dbf_cofk_union_refresh_queryable_work(input_work_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_union_refresh_work_desc(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_union_refresh_work_desc(input_work_id character varying) RETURNS void
    LANGUAGE plpgsql
    AS $$

declare
  old_desc text;
  new_desc text;
begin

  select description into old_desc from cofk_union_work where work_id = input_work_id;

  select dbf_cofk_union_get_work_desc( input_work_id ) into new_desc;

  if coalesce( old_desc, '' ) != coalesce( new_desc, '' ) then
    update cofk_union_work
    set description = new_desc
    where work_id = input_work_id;
  end if;
  return;
end;

$$;


ALTER FUNCTION public.dbf_cofk_union_refresh_work_desc(input_work_id character varying) OWNER TO postgres;

--
-- Name: dbf_cofk_update_user_details(character varying, character varying, character varying, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_cofk_update_user_details(input_username character varying, input_surname character varying, input_forename character varying, input_email text) RETURNS character varying
    LANGUAGE plpgsql STRICT SECURITY DEFINER
    AS $$

begin
  update cofk_users
  set
    surname = input_surname,
    forename = input_forename,
    email = input_email
  where
    username = input_username;

  return input_username;
end;

$$;


ALTER FUNCTION public.dbf_cofk_update_user_details(input_username character varying, input_surname character varying, input_forename character varying, input_email text) OWNER TO postgres;

--
-- Name: dbf_exec_with_rowcount(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.dbf_exec_with_rowcount(statement_parm text) RETURNS integer
    LANGUAGE plpgsql
    AS $$


declare
  rowcount_var integer := 0;
begin
  execute statement_parm;
  get diagnostics rowcount_var = row_count;
  return rowcount_var;
end;


$$;


ALTER FUNCTION public.dbf_exec_with_rowcount(statement_parm text) OWNER TO postgres;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: cofk_union_queryable_work; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_queryable_work (
    iwork_id integer NOT NULL,
    work_id character varying(100) NOT NULL,
    description text,
    date_of_work_std date,
    date_of_work_std_year integer,
    date_of_work_std_month integer,
    date_of_work_std_day integer,
    date_of_work_as_marked character varying(250),
    date_of_work_inferred smallint DEFAULT 0 NOT NULL,
    date_of_work_uncertain smallint DEFAULT 0 NOT NULL,
    date_of_work_approx smallint DEFAULT 0 NOT NULL,
    creators_searchable text DEFAULT ''::text NOT NULL,
    creators_for_display text DEFAULT ''::text NOT NULL,
    authors_as_marked text,
    notes_on_authors text,
    authors_inferred smallint DEFAULT 0 NOT NULL,
    authors_uncertain smallint DEFAULT 0 NOT NULL,
    addressees_searchable text DEFAULT ''::text NOT NULL,
    addressees_for_display text DEFAULT ''::text NOT NULL,
    addressees_as_marked text,
    addressees_inferred smallint DEFAULT 0 NOT NULL,
    addressees_uncertain smallint DEFAULT 0 NOT NULL,
    places_from_searchable text DEFAULT ''::text NOT NULL,
    places_from_for_display text DEFAULT ''::text NOT NULL,
    origin_as_marked text,
    origin_inferred smallint DEFAULT 0 NOT NULL,
    origin_uncertain smallint DEFAULT 0 NOT NULL,
    places_to_searchable text DEFAULT ''::text NOT NULL,
    places_to_for_display text DEFAULT ''::text NOT NULL,
    destination_as_marked text,
    destination_inferred smallint DEFAULT 0 NOT NULL,
    destination_uncertain smallint DEFAULT 0 NOT NULL,
    manifestations_searchable text DEFAULT ''::text NOT NULL,
    manifestations_for_display text DEFAULT ''::text NOT NULL,
    abstract text,
    keywords text,
    people_mentioned text,
    images text,
    related_resources text,
    language_of_work character varying(255),
    work_is_translation smallint DEFAULT 0 NOT NULL,
    flags text,
    edit_status character varying(3) DEFAULT ''::character varying NOT NULL,
    general_notes text,
    original_catalogue character varying(100) DEFAULT ''::character varying NOT NULL,
    accession_code character varying(1000),
    work_to_be_deleted smallint DEFAULT 0 NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL,
    drawer character varying(50),
    editors_notes text,
    manifestation_type character varying(50),
    original_notes text,
    relevant_to_cofk character varying(1) DEFAULT ''::character varying NOT NULL,
    subjects text
);


ALTER TABLE public.cofk_union_queryable_work OWNER TO postgres;

--
-- Name: cofk_cardindex_work_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_cardindex_work_view AS
 SELECT cofk_union_queryable_work.description,
        CASE cofk_union_queryable_work.relevant_to_cofk
            WHEN 'Y'::text THEN 'Yes'::character varying
            WHEN 'N'::text THEN 'No'::character varying
            ELSE '?'::character varying
        END AS relevant_to_cofk,
    cofk_union_queryable_work.editors_notes,
    (rpad(cofk_union_queryable_work.creators_searchable, 500) || cofk_union_queryable_work.addressees_searchable) AS sender_or_recipient,
    cofk_union_queryable_work.date_of_work_as_marked,
    cofk_union_queryable_work.date_of_work_std_year,
    cofk_union_queryable_work.date_of_work_std_month,
    cofk_union_queryable_work.date_of_work_std_day,
    cofk_union_queryable_work.date_of_work_std,
    cofk_union_queryable_work.creators_searchable,
    cofk_union_queryable_work.places_from_searchable,
    cofk_union_queryable_work.addressees_searchable,
    cofk_union_queryable_work.creators_for_display,
    cofk_union_queryable_work.places_from_for_display,
    cofk_union_queryable_work.addressees_for_display,
    cofk_union_queryable_work.flags,
    cofk_union_queryable_work.images,
    cofk_union_queryable_work.manifestation_type,
    cofk_union_queryable_work.manifestations_searchable,
    cofk_union_queryable_work.manifestations_for_display,
    cofk_union_queryable_work.language_of_work,
    cofk_union_queryable_work.abstract,
    cofk_union_queryable_work.drawer,
    cofk_union_queryable_work.people_mentioned,
    cofk_union_queryable_work.original_notes,
        CASE
            WHEN (cofk_union_queryable_work.work_to_be_deleted = 1) THEN 'Yes'::text
            ELSE 'No'::text
        END AS work_to_be_deleted,
    cofk_union_queryable_work.iwork_id,
    cofk_union_queryable_work.change_timestamp,
    cofk_union_queryable_work.change_user,
    cofk_union_queryable_work.work_id
   FROM public.cofk_union_queryable_work
  WHERE ((cofk_union_queryable_work.date_of_work_std <> '1900-01-01'::date) AND ((cofk_union_queryable_work.original_catalogue)::text = 'cardindex'::text));


ALTER TABLE public.cofk_cardindex_work_view OWNER TO postgres;

--
-- Name: cofk_cardindex_compact_work_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_cardindex_compact_work_view AS
 SELECT cofk_cardindex_work_view.description,
    cofk_cardindex_work_view.relevant_to_cofk,
    cofk_cardindex_work_view.editors_notes,
    cofk_cardindex_work_view.date_of_work_as_marked,
    cofk_cardindex_work_view.date_of_work_std_year,
    cofk_cardindex_work_view.date_of_work_std_month,
    cofk_cardindex_work_view.date_of_work_std_day,
    cofk_cardindex_work_view.date_of_work_std,
    cofk_cardindex_work_view.sender_or_recipient,
    cofk_cardindex_work_view.creators_searchable,
    cofk_cardindex_work_view.addressees_searchable,
    cofk_cardindex_work_view.places_from_searchable,
    cofk_cardindex_work_view.flags,
    cofk_cardindex_work_view.images,
    cofk_cardindex_work_view.manifestation_type,
    cofk_cardindex_work_view.manifestations_searchable,
    cofk_cardindex_work_view.manifestations_for_display,
    cofk_cardindex_work_view.language_of_work,
    cofk_cardindex_work_view.abstract,
    cofk_cardindex_work_view.drawer,
    cofk_cardindex_work_view.work_to_be_deleted,
    cofk_cardindex_work_view.iwork_id,
    cofk_cardindex_work_view.change_timestamp,
    cofk_cardindex_work_view.change_user
   FROM public.cofk_cardindex_work_view;


ALTER TABLE public.cofk_cardindex_compact_work_view OWNER TO postgres;

--
-- Name: cofk_collect_addressee_of_work; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_addressee_of_work (
    upload_id integer NOT NULL,
    addressee_id integer NOT NULL,
    iperson_id integer NOT NULL,
    iwork_id integer NOT NULL,
    notes_on_addressee text,
    _id character varying(32)
);


ALTER TABLE public.cofk_collect_addressee_of_work OWNER TO postgres;

--
-- Name: cofk_collect_author_of_work; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_author_of_work (
    upload_id integer NOT NULL,
    author_id integer NOT NULL,
    iperson_id integer NOT NULL,
    iwork_id integer NOT NULL,
    notes_on_author text,
    _id character varying(32)
);


ALTER TABLE public.cofk_collect_author_of_work OWNER TO postgres;

--
-- Name: cofk_collect_destination_of_work; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_destination_of_work (
    upload_id integer NOT NULL,
    destination_id integer NOT NULL,
    location_id integer NOT NULL,
    iwork_id integer NOT NULL,
    notes_on_destination text,
    _id character varying(32)
);


ALTER TABLE public.cofk_collect_destination_of_work OWNER TO postgres;

--
-- Name: cofk_collect_image_of_manif; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_image_of_manif (
    upload_id integer NOT NULL,
    manifestation_id integer NOT NULL,
    image_filename character varying(2000) NOT NULL,
    _id character varying(32),
    iwork_id integer
);


ALTER TABLE public.cofk_collect_image_of_manif OWNER TO postgres;

--
-- Name: cofk_collect_institution; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_institution (
    upload_id integer NOT NULL,
    institution_id integer NOT NULL,
    union_institution_id integer,
    institution_name text DEFAULT ''::text NOT NULL,
    institution_city text DEFAULT ''::text NOT NULL,
    institution_country text DEFAULT ''::text NOT NULL,
    upload_name character varying(254),
    _id character varying(32),
    institution_synonyms text,
    institution_city_synonyms text,
    institution_country_synonyms text
);


ALTER TABLE public.cofk_collect_institution OWNER TO postgres;

--
-- Name: cofk_collect_institution_resource; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_institution_resource (
    upload_id integer NOT NULL,
    resource_id integer NOT NULL,
    institution_id integer NOT NULL,
    resource_name text DEFAULT ''::text NOT NULL,
    resource_details text DEFAULT ''::text NOT NULL,
    resource_url text DEFAULT ''::text NOT NULL
);


ALTER TABLE public.cofk_collect_institution_resource OWNER TO postgres;

--
-- Name: cofk_collect_language_of_work; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_language_of_work (
    upload_id integer NOT NULL,
    language_of_work_id integer NOT NULL,
    iwork_id integer NOT NULL,
    language_code character varying(3) NOT NULL,
    _id character varying(32)
);


ALTER TABLE public.cofk_collect_language_of_work OWNER TO postgres;

--
-- Name: cofk_collect_location; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_location (
    upload_id integer NOT NULL,
    location_id integer NOT NULL,
    union_location_id integer,
    location_name character varying(500) DEFAULT ''::character varying NOT NULL,
    element_1_eg_room character varying(100) DEFAULT ''::character varying NOT NULL,
    element_2_eg_building character varying(100) DEFAULT ''::character varying NOT NULL,
    element_3_eg_parish character varying(100) DEFAULT ''::character varying NOT NULL,
    element_4_eg_city character varying(100) DEFAULT ''::character varying NOT NULL,
    element_5_eg_county character varying(100) DEFAULT ''::character varying NOT NULL,
    element_6_eg_country character varying(100) DEFAULT ''::character varying NOT NULL,
    element_7_eg_empire character varying(100) DEFAULT ''::character varying NOT NULL,
    notes_on_place text,
    editors_notes text,
    upload_name character varying(254),
    _id character varying(32),
    location_synonyms text,
    latitude character varying(20),
    longitude character varying(20)
);


ALTER TABLE public.cofk_collect_location OWNER TO postgres;

--
-- Name: cofk_collect_location_resource; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_location_resource (
    upload_id integer NOT NULL,
    resource_id integer NOT NULL,
    location_id integer NOT NULL,
    resource_name text DEFAULT ''::text NOT NULL,
    resource_details text DEFAULT ''::text NOT NULL,
    resource_url text DEFAULT ''::text NOT NULL
);


ALTER TABLE public.cofk_collect_location_resource OWNER TO postgres;

--
-- Name: cofk_collect_manifestation; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_manifestation (
    upload_id integer NOT NULL,
    manifestation_id integer NOT NULL,
    iwork_id integer NOT NULL,
    union_manifestation_id character varying(100),
    manifestation_type character varying(3) DEFAULT ''::character varying NOT NULL,
    repository_id integer,
    id_number_or_shelfmark character varying(500),
    printed_edition_details text,
    manifestation_notes text,
    image_filenames text,
    upload_name character varying(254),
    _id character varying(32)
);


ALTER TABLE public.cofk_collect_manifestation OWNER TO postgres;

--
-- Name: cofk_collect_occupation_of_person; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_occupation_of_person (
    upload_id integer NOT NULL,
    occupation_of_person_id integer NOT NULL,
    iperson_id integer NOT NULL,
    occupation_id integer NOT NULL
);


ALTER TABLE public.cofk_collect_occupation_of_person OWNER TO postgres;

--
-- Name: cofk_collect_origin_of_work; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_origin_of_work (
    upload_id integer NOT NULL,
    origin_id integer NOT NULL,
    location_id integer NOT NULL,
    iwork_id integer NOT NULL,
    notes_on_origin text,
    _id character varying(32)
);


ALTER TABLE public.cofk_collect_origin_of_work OWNER TO postgres;

--
-- Name: cofk_collect_person; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_person (
    upload_id integer NOT NULL,
    iperson_id integer NOT NULL,
    union_iperson_id integer,
    person_id character varying(100),
    primary_name character varying(200) NOT NULL,
    alternative_names text,
    roles_or_titles text,
    gender character varying(1) DEFAULT ''::character varying NOT NULL,
    is_organisation character varying(1) DEFAULT ''::character varying NOT NULL,
    organisation_type integer,
    date_of_birth_year integer,
    date_of_birth_month integer,
    date_of_birth_day integer,
    date_of_birth_is_range smallint DEFAULT 0 NOT NULL,
    date_of_birth2_year integer,
    date_of_birth2_month integer,
    date_of_birth2_day integer,
    date_of_birth_inferred smallint DEFAULT 0 NOT NULL,
    date_of_birth_uncertain smallint DEFAULT 0 NOT NULL,
    date_of_birth_approx smallint DEFAULT 0 NOT NULL,
    date_of_death_year integer,
    date_of_death_month integer,
    date_of_death_day integer,
    date_of_death_is_range smallint DEFAULT 0 NOT NULL,
    date_of_death2_year integer,
    date_of_death2_month integer,
    date_of_death2_day integer,
    date_of_death_inferred smallint DEFAULT 0 NOT NULL,
    date_of_death_uncertain smallint DEFAULT 0 NOT NULL,
    date_of_death_approx smallint DEFAULT 0 NOT NULL,
    flourished_year integer,
    flourished_month integer,
    flourished_day integer,
    flourished_is_range smallint DEFAULT 0 NOT NULL,
    flourished2_year integer,
    flourished2_month integer,
    flourished2_day integer,
    notes_on_person text,
    editors_notes text,
    upload_name character varying(254),
    _id character varying(32)
);


ALTER TABLE public.cofk_collect_person OWNER TO postgres;

--
-- Name: cofk_collect_person_mentioned_in_work; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_person_mentioned_in_work (
    upload_id integer NOT NULL,
    mention_id integer NOT NULL,
    iperson_id integer NOT NULL,
    iwork_id integer NOT NULL,
    notes_on_person_mentioned text,
    _id character varying(32)
);


ALTER TABLE public.cofk_collect_person_mentioned_in_work OWNER TO postgres;

--
-- Name: cofk_collect_person_resource; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_person_resource (
    upload_id integer NOT NULL,
    resource_id integer NOT NULL,
    iperson_id integer NOT NULL,
    resource_name text DEFAULT ''::text NOT NULL,
    resource_details text DEFAULT ''::text NOT NULL,
    resource_url text DEFAULT ''::text NOT NULL
);


ALTER TABLE public.cofk_collect_person_resource OWNER TO postgres;

--
-- Name: cofk_collect_place_mentioned_in_work; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_place_mentioned_in_work (
    upload_id integer NOT NULL,
    mention_id integer NOT NULL,
    location_id integer NOT NULL,
    iwork_id integer NOT NULL,
    notes_on_place_mentioned text,
    _id character varying(32)
);


ALTER TABLE public.cofk_collect_place_mentioned_in_work OWNER TO postgres;

--
-- Name: cofk_collect_status; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_status (
    status_id integer NOT NULL,
    status_desc character varying(100) NOT NULL,
    editable smallint DEFAULT 1 NOT NULL
);


ALTER TABLE public.cofk_collect_status OWNER TO postgres;

--
-- Name: cofk_collect_subject_of_work; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_subject_of_work (
    upload_id integer NOT NULL,
    subject_of_work_id integer NOT NULL,
    iwork_id integer NOT NULL,
    subject_id integer NOT NULL
);


ALTER TABLE public.cofk_collect_subject_of_work OWNER TO postgres;

--
-- Name: cofk_sessions_session_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_sessions_session_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_sessions_session_id_seq OWNER TO postgres;

--
-- Name: cofk_collect_tool_session; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_tool_session (
    session_id integer DEFAULT nextval('public.cofk_sessions_session_id_seq'::regclass) NOT NULL,
    session_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    session_code text,
    username character varying(100)
);


ALTER TABLE public.cofk_collect_tool_session OWNER TO postgres;

--
-- Name: cofk_collect_tool_user_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_collect_tool_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_collect_tool_user_id_seq OWNER TO postgres;

--
-- Name: cofk_collect_tool_user; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_tool_user (
    tool_user_id integer DEFAULT nextval('public.cofk_collect_tool_user_id_seq'::regclass) NOT NULL,
    tool_user_email character varying(100) NOT NULL,
    tool_user_surname character varying(100) NOT NULL,
    tool_user_forename character varying(100) NOT NULL,
    tool_user_pw character varying(100) NOT NULL
);


ALTER TABLE public.cofk_collect_tool_user OWNER TO postgres;

--
-- Name: cofk_collect_upload_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_collect_upload_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_collect_upload_id_seq OWNER TO postgres;

--
-- Name: cofk_collect_upload; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_upload (
    upload_id integer DEFAULT nextval('public.cofk_collect_upload_id_seq'::regclass) NOT NULL,
    upload_username character varying(100) NOT NULL,
    upload_description text,
    upload_status integer DEFAULT 1 NOT NULL,
    upload_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    total_works integer DEFAULT 0 NOT NULL,
    works_accepted integer DEFAULT 0 NOT NULL,
    works_rejected integer DEFAULT 0 NOT NULL,
    uploader_email character varying(250) DEFAULT ''::character varying NOT NULL,
    _id character varying(32),
    upload_name character varying(254)
);


ALTER TABLE public.cofk_collect_upload OWNER TO postgres;

--
-- Name: cofk_collect_work; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_work (
    upload_id integer NOT NULL,
    iwork_id integer NOT NULL,
    union_iwork_id integer,
    work_id character varying(100),
    date_of_work_as_marked character varying(250),
    original_calendar character varying(2) DEFAULT ''::character varying NOT NULL,
    date_of_work_std_year integer,
    date_of_work_std_month integer,
    date_of_work_std_day integer,
    date_of_work2_std_year integer,
    date_of_work2_std_month integer,
    date_of_work2_std_day integer,
    date_of_work_std_is_range smallint DEFAULT 0 NOT NULL,
    date_of_work_inferred smallint DEFAULT 0 NOT NULL,
    date_of_work_uncertain smallint DEFAULT 0 NOT NULL,
    date_of_work_approx smallint DEFAULT 0 NOT NULL,
    notes_on_date_of_work text,
    authors_as_marked text,
    authors_inferred smallint DEFAULT 0 NOT NULL,
    authors_uncertain smallint DEFAULT 0 NOT NULL,
    notes_on_authors text,
    addressees_as_marked text,
    addressees_inferred smallint DEFAULT 0 NOT NULL,
    addressees_uncertain smallint DEFAULT 0 NOT NULL,
    notes_on_addressees text,
    destination_id integer,
    destination_as_marked text,
    destination_inferred smallint DEFAULT 0 NOT NULL,
    destination_uncertain smallint DEFAULT 0 NOT NULL,
    origin_id integer,
    origin_as_marked text,
    origin_inferred smallint DEFAULT 0 NOT NULL,
    origin_uncertain smallint DEFAULT 0 NOT NULL,
    abstract text,
    keywords text,
    language_of_work character varying(255),
    incipit text,
    excipit text,
    accession_code character varying(250),
    notes_on_letter text,
    notes_on_people_mentioned text,
    upload_status integer DEFAULT 1 NOT NULL,
    editors_notes text,
    _id character varying(32),
    date_of_work2_approx smallint DEFAULT 0 NOT NULL,
    date_of_work2_inferred smallint DEFAULT 0 NOT NULL,
    date_of_work2_uncertain smallint DEFAULT 0 NOT NULL,
    mentioned_as_marked text,
    mentioned_inferred smallint DEFAULT 0 NOT NULL,
    mentioned_uncertain smallint DEFAULT 0 NOT NULL,
    notes_on_destination text,
    notes_on_origin text,
    notes_on_place_mentioned text,
    place_mentioned_as_marked text,
    place_mentioned_inferred smallint DEFAULT 0 NOT NULL,
    place_mentioned_uncertain smallint DEFAULT 0 NOT NULL,
    upload_name character varying(254),
    explicit text
);


ALTER TABLE public.cofk_collect_work OWNER TO postgres;

--
-- Name: cofk_collect_work_resource; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_work_resource (
    upload_id integer NOT NULL,
    resource_id integer NOT NULL,
    iwork_id integer NOT NULL,
    resource_name text DEFAULT ''::text NOT NULL,
    resource_details text DEFAULT ''::text NOT NULL,
    resource_url text DEFAULT ''::text NOT NULL,
    _id character varying(32)
);


ALTER TABLE public.cofk_collect_work_resource OWNER TO postgres;

--
-- Name: cofk_collect_work_summary; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_collect_work_summary (
    upload_id integer NOT NULL,
    work_id_in_tool integer NOT NULL,
    source_of_data character varying(250),
    notes_on_letter text,
    date_of_work character varying(32),
    date_of_work_as_marked character varying(250),
    original_calendar character varying(30),
    date_of_work_is_range character varying(30),
    date_of_work_inferred character varying(30),
    date_of_work_uncertain character varying(30),
    date_of_work_approx character varying(30),
    notes_on_date_of_work text,
    authors text,
    authors_as_marked text,
    authors_inferred character varying(30),
    authors_uncertain character varying(30),
    notes_on_authors text,
    addressees text,
    addressees_as_marked text,
    addressees_inferred character varying(30),
    addressees_uncertain character varying(30),
    notes_on_addressees text,
    destination text,
    destination_as_marked text,
    destination_inferred character varying(30),
    destination_uncertain character varying(30),
    origin text,
    origin_as_marked text,
    origin_inferred character varying(30),
    origin_uncertain character varying(30),
    abstract text,
    keywords text,
    languages_of_work text,
    subjects_of_work text,
    incipit text,
    excipit text,
    people_mentioned text,
    notes_on_people_mentioned text,
    places_mentioned text,
    manifestations text,
    related_resources text,
    editors_notes text
);


ALTER TABLE public.cofk_collect_work_summary OWNER TO postgres;

--
-- Name: cofk_collect_work_summary_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_collect_work_summary_view AS
 SELECT ws.source_of_data,
    u.uploader_email,
    s.status_desc,
    w.union_iwork_id,
    ws.editors_notes,
    ws.date_of_work,
    ws.date_of_work_as_marked,
    ws.original_calendar,
    ws.notes_on_date_of_work,
    ws.authors,
    ws.authors_as_marked,
    ws.notes_on_authors,
    ws.origin,
    ws.origin_as_marked,
    ws.addressees,
    ws.addressees_as_marked,
    ws.notes_on_addressees,
    ws.destination,
    ws.destination_as_marked,
    ws.manifestations,
    ws.abstract,
    ws.keywords,
    ws.languages_of_work,
    ws.subjects_of_work,
    ws.incipit,
    ws.excipit,
    ws.people_mentioned,
    ws.notes_on_people_mentioned,
    ws.places_mentioned,
    btrim((((((((((((
        CASE
            WHEN ((ws.date_of_work_is_range)::text > ''::text) THEN ((' ~ '::text || (ws.date_of_work_is_range)::text) || '
'::text)
            ELSE ''::text
        END ||
        CASE
            WHEN ((ws.date_of_work_inferred)::text > ''::text) THEN ((' ~ '::text || (ws.date_of_work_inferred)::text) || '
'::text)
            ELSE ''::text
        END) ||
        CASE
            WHEN ((ws.date_of_work_uncertain)::text > ''::text) THEN ((' ~ '::text || (ws.date_of_work_uncertain)::text) || '
'::text)
            ELSE ''::text
        END) ||
        CASE
            WHEN ((ws.date_of_work_approx)::text > ''::text) THEN ((' ~ '::text || (ws.date_of_work_approx)::text) || '
'::text)
            ELSE ''::text
        END) ||
        CASE
            WHEN ((ws.authors_inferred)::text > ''::text) THEN ((' ~ '::text || (ws.authors_inferred)::text) || '
'::text)
            ELSE ''::text
        END) ||
        CASE
            WHEN ((ws.authors_uncertain)::text > ''::text) THEN ((' ~ '::text || (ws.authors_uncertain)::text) || '
'::text)
            ELSE ''::text
        END) ||
        CASE
            WHEN ((ws.origin_inferred)::text > ''::text) THEN ((' ~ '::text || (ws.origin_inferred)::text) || '
'::text)
            ELSE ''::text
        END) ||
        CASE
            WHEN ((ws.origin_uncertain)::text > ''::text) THEN ((' ~ '::text || (ws.origin_uncertain)::text) || '
'::text)
            ELSE ''::text
        END) ||
        CASE
            WHEN ((ws.addressees_inferred)::text > ''::text) THEN ((' ~ '::text || (ws.addressees_inferred)::text) || '
'::text)
            ELSE ''::text
        END) ||
        CASE
            WHEN ((ws.addressees_uncertain)::text > ''::text) THEN ((' ~ '::text || (ws.addressees_uncertain)::text) || '
'::text)
            ELSE ''::text
        END) ||
        CASE
            WHEN ((ws.destination_inferred)::text > ''::text) THEN ((' ~ '::text || (ws.destination_inferred)::text) || '
'::text)
            ELSE ''::text
        END) ||
        CASE
            WHEN ((ws.destination_uncertain)::text > ''::text) THEN ((' ~ '::text || (ws.destination_uncertain)::text) || '
'::text)
            ELSE ''::text
        END)) AS issues,
    ws.notes_on_letter,
    ws.related_resources,
    ws.upload_id,
    ws.work_id_in_tool,
    ((ws.upload_id * 1000000) + ws.work_id_in_tool) AS contributed_work_id
   FROM public.cofk_collect_upload u,
    public.cofk_collect_work_summary ws,
    public.cofk_collect_work w,
    public.cofk_collect_status s
  WHERE ((ws.upload_id = u.upload_id) AND (ws.upload_id = w.upload_id) AND (ws.work_id_in_tool = w.iwork_id) AND (w.upload_status = s.status_id));


ALTER TABLE public.cofk_collect_work_summary_view OWNER TO postgres;

--
-- Name: cofk_help_options_option_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_help_options_option_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_help_options_option_id_seq OWNER TO postgres;

--
-- Name: cofk_help_options; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_help_options (
    option_id integer DEFAULT nextval('public.cofk_help_options_option_id_seq'::regclass) NOT NULL,
    menu_item_id integer,
    button_name character varying(100) DEFAULT ''::character varying NOT NULL,
    help_page_id integer NOT NULL,
    order_in_manual integer DEFAULT 0 NOT NULL,
    menu_depth integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.cofk_help_options OWNER TO postgres;

--
-- Name: cofk_help_pages_page_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_help_pages_page_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_help_pages_page_id_seq OWNER TO postgres;

--
-- Name: cofk_help_pages; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_help_pages (
    page_id integer DEFAULT nextval('public.cofk_help_pages_page_id_seq'::regclass) NOT NULL,
    page_title character varying(500) NOT NULL,
    custom_url character varying(500),
    published_text text DEFAULT 'Sorry, no help currently available.'::text NOT NULL,
    draft_text text
);


ALTER TABLE public.cofk_help_pages OWNER TO postgres;

--
-- Name: cofk_lookup_catalogue_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_lookup_catalogue_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_lookup_catalogue_id_seq OWNER TO postgres;

--
-- Name: cofk_lookup_catalogue; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_lookup_catalogue (
    catalogue_id integer DEFAULT nextval('public.cofk_lookup_catalogue_id_seq'::regclass) NOT NULL,
    catalogue_code character varying(100) DEFAULT ''::character varying NOT NULL,
    catalogue_name character varying(500) DEFAULT ''::character varying NOT NULL,
    is_in_union integer DEFAULT 1 NOT NULL,
    publish_status smallint DEFAULT 0 NOT NULL
);


ALTER TABLE public.cofk_lookup_catalogue OWNER TO postgres;

--
-- Name: cofk_lookup_document_type_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_lookup_document_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_lookup_document_type_id_seq OWNER TO postgres;

--
-- Name: cofk_lookup_document_type; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_lookup_document_type (
    document_type_id integer DEFAULT nextval('public.cofk_lookup_document_type_id_seq'::regclass) NOT NULL,
    document_type_code character varying(3) NOT NULL,
    document_type_desc character varying(100) NOT NULL
);


ALTER TABLE public.cofk_lookup_document_type OWNER TO postgres;

--
-- Name: cofk_menu_item_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_menu_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_menu_item_id_seq OWNER TO postgres;

--
-- Name: cofk_menu_order_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_menu_order_seq
    START WITH 1
    INCREMENT BY 10
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_menu_order_seq OWNER TO postgres;

--
-- Name: cofk_menu; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_menu (
    menu_item_id integer DEFAULT nextval('public.cofk_menu_item_id_seq'::regclass) NOT NULL,
    menu_item_name text NOT NULL,
    menu_order integer DEFAULT nextval('public.cofk_menu_order_seq'::regclass),
    parent_id integer,
    has_children integer DEFAULT 0 NOT NULL,
    class_name character varying(100),
    method_name character varying(100),
    user_restriction character varying(30) DEFAULT ''::character varying NOT NULL,
    hidden_parent integer,
    called_as_popup integer DEFAULT 0 NOT NULL,
    collection character varying(20) DEFAULT ''::character varying NOT NULL,
    CONSTRAINT cofk_chk_item_is_submenu_or_form CHECK ((((has_children = 0) AND (class_name IS NOT NULL) AND (method_name IS NOT NULL)) OR ((has_children = 1) AND (class_name IS NULL) AND (method_name IS NULL)))),
    CONSTRAINT cofk_chk_menu_item_called_as_popup CHECK (((called_as_popup = 0) OR (called_as_popup = 1)))
);


ALTER TABLE public.cofk_menu OWNER TO postgres;

--
-- Name: cofk_report_groups_report_group_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_report_groups_report_group_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_report_groups_report_group_id_seq OWNER TO postgres;

--
-- Name: cofk_report_groups; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_report_groups (
    report_group_id integer DEFAULT nextval('public.cofk_report_groups_report_group_id_seq'::regclass) NOT NULL,
    report_group_title text,
    report_group_order integer DEFAULT 1 NOT NULL,
    on_main_reports_menu integer DEFAULT 0 NOT NULL,
    report_group_code character varying(100)
);


ALTER TABLE public.cofk_report_groups OWNER TO postgres;

--
-- Name: cofk_report_outputs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_report_outputs (
    output_id character varying(250) DEFAULT ''::character varying NOT NULL,
    line_number integer DEFAULT 0 NOT NULL,
    line_text text
);


ALTER TABLE public.cofk_report_outputs OWNER TO postgres;

--
-- Name: cofk_reports_report_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_reports_report_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_reports_report_id_seq OWNER TO postgres;

--
-- Name: cofk_reports; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_reports (
    report_id integer DEFAULT nextval('public.cofk_reports_report_id_seq'::regclass) NOT NULL,
    report_title text,
    class_name character varying(40),
    method_name character varying(40),
    report_group_id integer,
    menu_item_id integer,
    has_csv_option integer DEFAULT 0 NOT NULL,
    is_dummy_option integer DEFAULT 0 NOT NULL,
    report_code character varying(100),
    parm_list text,
    parm_titles text,
    prompt_for_parms smallint DEFAULT 0 NOT NULL,
    default_parm_values text,
    parm_methods text,
    report_help text
);


ALTER TABLE public.cofk_reports OWNER TO postgres;

--
-- Name: cofk_roles_role_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_roles_role_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_roles_role_id_seq OWNER TO postgres;

--
-- Name: cofk_roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_roles (
    role_id integer DEFAULT nextval('public.cofk_roles_role_id_seq'::regclass) NOT NULL,
    role_code character varying(20) DEFAULT ''::character varying NOT NULL,
    role_name text DEFAULT ''::text NOT NULL
);


ALTER TABLE public.cofk_roles OWNER TO postgres;

--
-- Name: cofk_sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_sessions (
    session_id integer DEFAULT nextval('public.cofk_sessions_session_id_seq'::regclass) NOT NULL,
    session_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    session_code text,
    username character varying(100)
);


ALTER TABLE public.cofk_sessions OWNER TO postgres;

--
-- Name: cofk_union_audit_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_audit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_audit_id_seq OWNER TO postgres;

--
-- Name: cofk_union_audit_literal; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_audit_literal (
    audit_id integer DEFAULT nextval('public.cofk_union_audit_id_seq'::regclass) NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL,
    change_type character varying(3) NOT NULL,
    table_name character varying(100) NOT NULL,
    key_value_text character varying(100) NOT NULL,
    key_value_integer integer,
    key_decode text,
    column_name character varying(100) NOT NULL,
    new_column_value text,
    old_column_value text
);


ALTER TABLE public.cofk_union_audit_literal OWNER TO postgres;

--
-- Name: cofk_union_audit_relationship; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_audit_relationship (
    audit_id integer DEFAULT nextval('public.cofk_union_audit_id_seq'::regclass) NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL,
    change_type character varying(3) NOT NULL,
    left_table_name character varying(100) NOT NULL,
    left_id_value_new character varying(100) DEFAULT ''::character varying NOT NULL,
    left_id_decode_new text DEFAULT ''::text NOT NULL,
    left_id_value_old character varying(100) DEFAULT ''::character varying NOT NULL,
    left_id_decode_old text DEFAULT ''::text NOT NULL,
    relationship_type character varying(100) NOT NULL,
    relationship_decode_left_to_right character varying(100) DEFAULT ''::character varying NOT NULL,
    relationship_decode_right_to_left character varying(100) DEFAULT ''::character varying NOT NULL,
    right_table_name character varying(100) NOT NULL,
    right_id_value_new character varying(100) DEFAULT ''::character varying NOT NULL,
    right_id_decode_new text DEFAULT ''::text NOT NULL,
    right_id_value_old character varying(100) DEFAULT ''::character varying NOT NULL,
    right_id_decode_old text DEFAULT ''::text NOT NULL
);


ALTER TABLE public.cofk_union_audit_relationship OWNER TO postgres;

--
-- Name: cofk_union_audit_trail_column_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_audit_trail_column_view AS
 SELECT max((((pg_attribute.attrelid)::integer * 100) + pg_attribute.attnum)) AS dummy_id,
    public.dbf_cofk_get_column_label((pg_attribute.attname)::character varying) AS changed_field
   FROM pg_attribute
  WHERE ((pg_attribute.attrelid IN ( SELECT pg_class.oid
           FROM pg_class
          WHERE ((pg_class.relkind = 'r'::"char") AND (pg_class.relname ~~ 'cofk_union%'::text) AND (pg_class.relname <> 'cofk_union_audit_literal'::name) AND (pg_class.relname <> 'cofk_union_audit_relationship'::name) AND (pg_class.relname <> 'cofk_union_relationship'::name) AND (pg_class.relname <> 'cofk_union_relationship_type'::name) AND (pg_class.relname <> 'cofk_union_queryable_work'::name)))) AND (pg_attribute.attnum > 0) AND (NOT pg_attribute.attisdropped) AND (pg_attribute.attname <> ALL (ARRAY['change_timestamp'::name, 'change_user'::name, 'creation_timestamp'::name, 'creation_user'::name])))
  GROUP BY (public.dbf_cofk_get_column_label((pg_attribute.attname)::character varying))
  ORDER BY (public.dbf_cofk_get_column_label((pg_attribute.attname)::character varying));


ALTER TABLE public.cofk_union_audit_trail_column_view OWNER TO postgres;

--
-- Name: cofk_union_audit_trail_table_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_audit_trail_table_view AS
 SELECT pg_class.oid AS dummy_id,
    initcap(replace(replace((pg_class.relname)::text, 'cofk_union_'::text, ''::text), '_'::text, ' '::text)) AS table_name
   FROM pg_class
  WHERE ((pg_class.relkind = 'r'::"char") AND (pg_class.relname ~~ 'cofk_union%'::text) AND (pg_class.relname <> 'cofk_union_audit_literal'::name) AND (pg_class.relname <> 'cofk_union_audit_relationship'::name) AND (pg_class.relname <> 'cofk_union_relationship'::name) AND (pg_class.relname <> 'cofk_union_relationship_type'::name) AND (pg_class.relname <> 'cofk_union_queryable_work'::name))
  ORDER BY (initcap(replace(replace((pg_class.relname)::text, 'cofk_union_'::text, ''::text), '_'::text, ' '::text)));


ALTER TABLE public.cofk_union_audit_trail_table_view OWNER TO postgres;

--
-- Name: cofk_union_person_iperson_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_person_iperson_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_person_iperson_id_seq OWNER TO postgres;

--
-- Name: cofk_union_person; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_person (
    person_id character varying(100) NOT NULL,
    foaf_name character varying(200) NOT NULL,
    skos_altlabel text,
    skos_hiddenlabel text,
    person_aliases text,
    date_of_birth_year integer,
    date_of_birth_month integer,
    date_of_birth_day integer,
    date_of_birth date,
    date_of_birth_inferred smallint DEFAULT 0 NOT NULL,
    date_of_birth_uncertain smallint DEFAULT 0 NOT NULL,
    date_of_birth_approx smallint DEFAULT 0 NOT NULL,
    date_of_death_year integer,
    date_of_death_month integer,
    date_of_death_day integer,
    date_of_death date,
    date_of_death_inferred smallint DEFAULT 0 NOT NULL,
    date_of_death_uncertain smallint DEFAULT 0 NOT NULL,
    date_of_death_approx smallint DEFAULT 0 NOT NULL,
    gender character varying(1) DEFAULT ''::character varying NOT NULL,
    is_organisation character varying(1) DEFAULT ''::character varying NOT NULL,
    iperson_id integer DEFAULT nextval('public.cofk_union_person_iperson_id_seq'::regclass) NOT NULL,
    creation_timestamp timestamp without time zone DEFAULT now(),
    creation_user character varying(50) DEFAULT "current_user"() NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL,
    editors_notes text,
    further_reading text,
    organisation_type integer,
    date_of_birth_calendar character varying(2) DEFAULT ''::character varying NOT NULL,
    date_of_birth_is_range smallint DEFAULT 0 NOT NULL,
    date_of_birth2_year integer,
    date_of_birth2_month integer,
    date_of_birth2_day integer,
    date_of_death_calendar character varying(2) DEFAULT ''::character varying NOT NULL,
    date_of_death_is_range smallint DEFAULT 0 NOT NULL,
    date_of_death2_year integer,
    date_of_death2_month integer,
    date_of_death2_day integer,
    flourished date,
    flourished_calendar character varying(2) DEFAULT ''::character varying NOT NULL,
    flourished_is_range smallint DEFAULT 0 NOT NULL,
    flourished_year integer,
    flourished_month integer,
    flourished_day integer,
    flourished2_year integer,
    flourished2_month integer,
    flourished2_day integer,
    uuid uuid DEFAULT public.uuid_generate_v4(),
    flourished_inferred smallint DEFAULT 0 NOT NULL,
    flourished_uncertain smallint DEFAULT 0 NOT NULL,
    flourished_approx smallint DEFAULT 0 NOT NULL,
    CONSTRAINT cofk_chk_union_person_date_of_birth_approx CHECK (((date_of_birth_approx = 0) OR (date_of_birth_approx = 1))),
    CONSTRAINT cofk_chk_union_person_date_of_birth_inferred CHECK (((date_of_birth_inferred = 0) OR (date_of_birth_inferred = 1))),
    CONSTRAINT cofk_chk_union_person_date_of_birth_uncertain CHECK (((date_of_birth_uncertain = 0) OR (date_of_birth_uncertain = 1))),
    CONSTRAINT cofk_chk_union_person_date_of_death_approx CHECK (((date_of_death_approx = 0) OR (date_of_death_approx = 1))),
    CONSTRAINT cofk_chk_union_person_date_of_death_inferred CHECK (((date_of_death_inferred = 0) OR (date_of_death_inferred = 1))),
    CONSTRAINT cofk_chk_union_person_date_of_death_uncertain CHECK (((date_of_death_uncertain = 0) OR (date_of_death_uncertain = 1))),
    CONSTRAINT cofk_union_chk_date_of_birth_is_range CHECK (((date_of_birth_is_range = 0) OR (date_of_birth_is_range = 1))),
    CONSTRAINT cofk_union_chk_date_of_death_is_range CHECK (((date_of_death_is_range = 0) OR (date_of_death_is_range = 1))),
    CONSTRAINT cofk_union_chk_flourished_is_range CHECK (((flourished_is_range = 0) OR (flourished_is_range = 1)))
);


ALTER TABLE public.cofk_union_person OWNER TO postgres;

--
-- Name: cofk_union_work_iwork_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_work_iwork_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_work_iwork_id_seq OWNER TO postgres;

--
-- Name: cofk_union_work; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_work (
    work_id character varying(100) NOT NULL,
    description text,
    date_of_work_as_marked character varying(250),
    original_calendar character varying(2) DEFAULT ''::character varying NOT NULL,
    date_of_work_std character varying(12) DEFAULT '9999-12-31'::character varying,
    date_of_work_std_gregorian character varying(12) DEFAULT '9999-12-31'::character varying,
    date_of_work_std_year integer,
    date_of_work_std_month integer,
    date_of_work_std_day integer,
    date_of_work2_std_year integer,
    date_of_work2_std_month integer,
    date_of_work2_std_day integer,
    date_of_work_std_is_range smallint DEFAULT 0 NOT NULL,
    date_of_work_inferred smallint DEFAULT 0 NOT NULL,
    date_of_work_uncertain smallint DEFAULT 0 NOT NULL,
    date_of_work_approx smallint DEFAULT 0 NOT NULL,
    authors_as_marked text,
    addressees_as_marked text,
    authors_inferred smallint DEFAULT 0 NOT NULL,
    authors_uncertain smallint DEFAULT 0 NOT NULL,
    addressees_inferred smallint DEFAULT 0 NOT NULL,
    addressees_uncertain smallint DEFAULT 0 NOT NULL,
    destination_as_marked text,
    origin_as_marked text,
    destination_inferred smallint DEFAULT 0 NOT NULL,
    destination_uncertain smallint DEFAULT 0 NOT NULL,
    origin_inferred smallint DEFAULT 0 NOT NULL,
    origin_uncertain smallint DEFAULT 0 NOT NULL,
    abstract text,
    keywords text,
    language_of_work character varying(255),
    work_is_translation smallint DEFAULT 0 NOT NULL,
    incipit text,
    explicit text,
    ps text,
    original_catalogue character varying(100) DEFAULT ''::character varying NOT NULL,
    accession_code character varying(1000),
    work_to_be_deleted smallint DEFAULT 0 NOT NULL,
    iwork_id integer DEFAULT nextval('public.cofk_union_work_iwork_id_seq'::regclass) NOT NULL,
    editors_notes text,
    edit_status character varying(3) DEFAULT ''::character varying NOT NULL,
    relevant_to_cofk character varying(3) DEFAULT 'Y'::character varying NOT NULL,
    creation_timestamp timestamp without time zone DEFAULT now(),
    creation_user character varying(50) DEFAULT "current_user"() NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4(),
    CONSTRAINT cofk_chk_union_work_addressees_inferred CHECK (((addressees_inferred = 0) OR (addressees_inferred = 1))),
    CONSTRAINT cofk_chk_union_work_addressees_uncertain CHECK (((addressees_uncertain = 0) OR (addressees_uncertain = 1))),
    CONSTRAINT cofk_chk_union_work_authors_inferred CHECK (((authors_inferred = 0) OR (authors_inferred = 1))),
    CONSTRAINT cofk_chk_union_work_authors_uncertain CHECK (((authors_uncertain = 0) OR (authors_uncertain = 1))),
    CONSTRAINT cofk_chk_union_work_date_of_work_approx CHECK (((date_of_work_approx = 0) OR (date_of_work_approx = 1))),
    CONSTRAINT cofk_chk_union_work_date_of_work_inferred CHECK (((date_of_work_inferred = 0) OR (date_of_work_inferred = 1))),
    CONSTRAINT cofk_chk_union_work_date_of_work_std_is_range CHECK (((date_of_work_std_is_range = 0) OR (date_of_work_std_is_range = 1))),
    CONSTRAINT cofk_chk_union_work_date_of_work_uncertain CHECK (((date_of_work_uncertain = 0) OR (date_of_work_uncertain = 1))),
    CONSTRAINT cofk_chk_union_work_destination_inferred CHECK (((destination_inferred = 0) OR (destination_inferred = 1))),
    CONSTRAINT cofk_chk_union_work_destination_uncertain CHECK (((destination_uncertain = 0) OR (destination_uncertain = 1))),
    CONSTRAINT cofk_chk_union_work_is_translation CHECK (((work_is_translation = 0) OR (work_is_translation = 1))),
    CONSTRAINT cofk_chk_union_work_origin_inferred CHECK (((origin_inferred = 0) OR (origin_inferred = 1))),
    CONSTRAINT cofk_chk_union_work_origin_uncertain CHECK (((origin_uncertain = 0) OR (origin_uncertain = 1))),
    CONSTRAINT cofk_chk_union_work_to_be_deleted CHECK (((work_to_be_deleted = 0) OR (work_to_be_deleted = 1)))
);


ALTER TABLE public.cofk_union_work OWNER TO postgres;

--
-- Name: cofk_union_audit_trail_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_audit_trail_view AS
 SELECT cofk_union_audit_literal.change_timestamp,
    cofk_union_audit_literal.change_user,
    public.dbf_cofk_get_table_label(cofk_union_audit_literal.table_name) AS table_name,
        CASE
            WHEN ((cofk_union_audit_literal.column_name)::text ~~ 'Relationship: %'::text) THEN
            CASE
                WHEN ((cofk_union_audit_literal.table_name)::text = 'cofk_union_work'::text) THEN COALESCE((( SELECT cofk_union_work.iwork_id
                   FROM public.cofk_union_work
                  WHERE ((cofk_union_work.work_id)::text = (cofk_union_audit_literal.key_value_text)::text)))::character varying, cofk_union_audit_literal.key_value_text)
                WHEN ((cofk_union_audit_literal.table_name)::text = 'cofk_union_person'::text) THEN COALESCE((( SELECT cofk_union_person.iperson_id
                   FROM public.cofk_union_person
                  WHERE ((cofk_union_person.person_id)::text = (cofk_union_audit_literal.key_value_text)::text)))::character varying, cofk_union_audit_literal.key_value_text)
                ELSE cofk_union_audit_literal.key_value_text
            END
            WHEN (cofk_union_audit_literal.key_value_integer IS NOT NULL) THEN (cofk_union_audit_literal.key_value_integer)::character varying
            ELSE cofk_union_audit_literal.key_value_text
        END AS changed_record_id,
    cofk_union_audit_literal.key_decode AS changed_record_desc,
    public.dbf_cofk_get_column_label(cofk_union_audit_literal.column_name) AS changed_field,
    cofk_union_audit_literal.change_type,
    ((
        CASE
            WHEN (cofk_union_audit_literal.new_column_value > ''::text) THEN ('New value: '::text || cofk_union_audit_literal.new_column_value)
            ELSE ''::text
        END ||
        CASE
            WHEN ((cofk_union_audit_literal.new_column_value > ''::text) AND (cofk_union_audit_literal.old_column_value > ''::text)) THEN '

'::text
            ELSE ''::text
        END) ||
        CASE
            WHEN (cofk_union_audit_literal.old_column_value > ''::text) THEN ('Old value: '::text || cofk_union_audit_literal.old_column_value)
            ELSE ''::text
        END) AS changes_made,
    cofk_union_audit_literal.audit_id AS audit_trail_entry
   FROM public.cofk_union_audit_literal;


ALTER TABLE public.cofk_union_audit_trail_view OWNER TO postgres;

--
-- Name: cofk_union_catalogue_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_catalogue_view AS
 SELECT cofk_lookup_catalogue.catalogue_id,
    cofk_lookup_catalogue.catalogue_code,
    cofk_lookup_catalogue.catalogue_name
   FROM public.cofk_lookup_catalogue
  WHERE (cofk_lookup_catalogue.is_in_union > 0);


ALTER TABLE public.cofk_union_catalogue_view OWNER TO postgres;

--
-- Name: cofk_union_comment_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_comment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_comment_id_seq OWNER TO postgres;

--
-- Name: cofk_union_comment; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_comment (
    comment_id integer DEFAULT nextval('public.cofk_union_comment_id_seq'::regclass) NOT NULL,
    comment text,
    creation_timestamp timestamp without time zone DEFAULT now(),
    creation_user character varying(50) DEFAULT "current_user"() NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4()
);


ALTER TABLE public.cofk_union_comment OWNER TO postgres;

--
-- Name: cofk_union_work_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_work_view AS
 SELECT cofk_union_queryable_work.description,
    cofk_union_queryable_work.editors_notes,
    (rpad(cofk_union_queryable_work.creators_searchable, 500) || cofk_union_queryable_work.addressees_searchable) AS sender_or_recipient,
    (rpad(cofk_union_queryable_work.places_from_searchable, 500) || cofk_union_queryable_work.places_to_searchable) AS place_to_or_from,
    cofk_union_queryable_work.date_of_work_as_marked,
    cofk_union_queryable_work.date_of_work_std_year,
    cofk_union_queryable_work.date_of_work_std_month,
    cofk_union_queryable_work.date_of_work_std_day,
    cofk_union_queryable_work.date_of_work_std,
    cofk_union_queryable_work.creators_searchable,
    cofk_union_queryable_work.notes_on_authors,
    cofk_union_queryable_work.places_from_searchable,
    cofk_union_queryable_work.origin_as_marked,
    cofk_union_queryable_work.addressees_searchable,
    cofk_union_queryable_work.places_to_searchable,
    cofk_union_queryable_work.destination_as_marked,
    cofk_union_queryable_work.creators_for_display,
    cofk_union_queryable_work.places_from_for_display,
    cofk_union_queryable_work.addressees_for_display,
    cofk_union_queryable_work.places_to_for_display,
    cofk_union_queryable_work.flags,
    cofk_union_queryable_work.images,
    cofk_union_queryable_work.manifestations_searchable,
    cofk_union_queryable_work.manifestations_for_display,
    cofk_union_queryable_work.related_resources,
    cofk_union_queryable_work.language_of_work,
    cofk_union_queryable_work.subjects,
    cofk_union_queryable_work.abstract,
    cofk_union_queryable_work.people_mentioned,
    cofk_union_queryable_work.keywords,
    cofk_union_queryable_work.general_notes,
    ( SELECT cofk_lookup_catalogue.catalogue_name
           FROM public.cofk_lookup_catalogue
          WHERE ((cofk_lookup_catalogue.catalogue_code)::text = (cofk_union_queryable_work.original_catalogue)::text)) AS original_catalogue,
    cofk_union_queryable_work.accession_code,
        CASE
            WHEN (cofk_union_queryable_work.work_to_be_deleted = 1) THEN 'Yes'::text
            ELSE 'No'::text
        END AS work_to_be_deleted,
    cofk_union_queryable_work.iwork_id,
    cofk_union_queryable_work.change_timestamp,
    cofk_union_queryable_work.change_user,
    cofk_union_queryable_work.work_id
   FROM public.cofk_union_queryable_work
  WHERE ((cofk_union_queryable_work.date_of_work_std <> '1900-01-01'::date) AND ((cofk_union_queryable_work.relevant_to_cofk)::text <> 'N'::text));


ALTER TABLE public.cofk_union_work_view OWNER TO postgres;

--
-- Name: cofk_union_compact_work_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_compact_work_view AS
 SELECT cofk_union_work_view.description,
    cofk_union_work_view.date_of_work_as_marked,
    cofk_union_work_view.date_of_work_std_year,
    cofk_union_work_view.date_of_work_std_month,
    cofk_union_work_view.date_of_work_std_day,
    cofk_union_work_view.date_of_work_std,
    cofk_union_work_view.sender_or_recipient,
    cofk_union_work_view.place_to_or_from,
    cofk_union_work_view.creators_searchable,
    cofk_union_work_view.notes_on_authors,
    cofk_union_work_view.addressees_searchable,
    cofk_union_work_view.places_from_searchable,
    cofk_union_work_view.places_to_searchable,
    cofk_union_work_view.flags,
    cofk_union_work_view.images,
    cofk_union_work_view.manifestations_searchable,
    cofk_union_work_view.manifestations_for_display,
    cofk_union_work_view.related_resources,
    cofk_union_work_view.language_of_work,
    cofk_union_work_view.subjects,
    cofk_union_work_view.abstract,
    cofk_union_work_view.general_notes,
    cofk_union_work_view.accession_code,
    cofk_union_work_view.original_catalogue,
    cofk_union_work_view.work_to_be_deleted,
    cofk_union_work_view.iwork_id,
    cofk_union_work_view.change_timestamp,
    cofk_union_work_view.change_user
   FROM public.cofk_union_work_view;


ALTER TABLE public.cofk_union_compact_work_view OWNER TO postgres;

--
-- Name: cofk_union_favourite_language; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_favourite_language (
    language_code character varying(3) NOT NULL
);


ALTER TABLE public.cofk_union_favourite_language OWNER TO postgres;

--
-- Name: iso_639_language_codes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.iso_639_language_codes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.iso_639_language_codes_id_seq OWNER TO postgres;

--
-- Name: iso_639_language_codes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.iso_639_language_codes (
    code_639_3 character varying(3) DEFAULT ''::character varying NOT NULL,
    code_639_1 character varying(2) DEFAULT ''::character varying NOT NULL,
    language_name character varying(100) DEFAULT ''::character varying NOT NULL,
    language_id integer DEFAULT nextval('public.iso_639_language_codes_id_seq'::regclass) NOT NULL
);


ALTER TABLE public.iso_639_language_codes OWNER TO postgres;

--
-- Name: cofk_union_favourite_language_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_favourite_language_view AS
 SELECT iso.code_639_3,
    iso.code_639_1,
    iso.language_name,
    iso.language_id
   FROM public.iso_639_language_codes iso,
    public.cofk_union_favourite_language fav
  WHERE ((fav.language_code)::text = (iso.code_639_3)::text);


ALTER TABLE public.cofk_union_favourite_language_view OWNER TO postgres;

--
-- Name: cofk_union_image_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_image_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_image_id_seq OWNER TO postgres;

--
-- Name: cofk_union_image; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_image (
    image_id integer DEFAULT nextval('public.cofk_union_image_id_seq'::regclass) NOT NULL,
    image_filename text,
    creation_timestamp timestamp without time zone DEFAULT now(),
    creation_user character varying(50) DEFAULT "current_user"() NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL,
    thumbnail text,
    can_be_displayed character varying(1) DEFAULT 'Y'::character varying NOT NULL,
    display_order integer DEFAULT 1 NOT NULL,
    licence_details text DEFAULT ''::text NOT NULL,
    licence_url character varying(2000) DEFAULT ''::character varying NOT NULL,
    credits character varying(2000) DEFAULT ''::character varying NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4()
);


ALTER TABLE public.cofk_union_image OWNER TO postgres;

--
-- Name: cofk_union_institution_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_institution_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_institution_id_seq OWNER TO postgres;

--
-- Name: cofk_union_institution; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_institution (
    institution_id integer DEFAULT nextval('public.cofk_union_institution_id_seq'::regclass) NOT NULL,
    institution_name text DEFAULT ''::text NOT NULL,
    institution_synonyms text DEFAULT ''::text NOT NULL,
    institution_city text DEFAULT ''::text NOT NULL,
    institution_city_synonyms text DEFAULT ''::text NOT NULL,
    institution_country text DEFAULT ''::text NOT NULL,
    institution_country_synonyms text DEFAULT ''::text NOT NULL,
    creation_timestamp timestamp without time zone DEFAULT now(),
    creation_user character varying(50) DEFAULT "current_user"() NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL,
    editors_notes text,
    uuid uuid DEFAULT public.uuid_generate_v4()
);


ALTER TABLE public.cofk_union_institution OWNER TO postgres;

--
-- Name: cofk_union_institution_query_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_institution_query_view AS
 SELECT cofk_union_institution.institution_id,
    cofk_union_institution.institution_name,
    cofk_union_institution.institution_synonyms,
    cofk_union_institution.institution_city,
    cofk_union_institution.institution_city_synonyms,
    cofk_union_institution.institution_country,
    cofk_union_institution.institution_country_synonyms,
    public.dbf_cofk_union_list_related_resources('cofk_union_institution'::character varying, (cofk_union_institution.institution_id)::character varying, 1) AS related_resources,
    cofk_union_institution.editors_notes,
    public.dbf_cofk_union_list_images_of_entity('cofk_union_institution'::character varying, (cofk_union_institution.institution_id)::character varying) AS images,
    cofk_union_institution.creation_timestamp,
    cofk_union_institution.creation_user,
    cofk_union_institution.change_timestamp,
    cofk_union_institution.change_user
   FROM public.cofk_union_institution;


ALTER TABLE public.cofk_union_institution_query_view OWNER TO postgres;

--
-- Name: cofk_union_institution_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_institution_view AS
 SELECT 0 AS institution_id,
    ''::text AS institution_name
UNION
 SELECT cofk_union_institution.institution_id,
    ((
        CASE
            WHEN (btrim(cofk_union_institution.institution_country) > ''::text) THEN (btrim(cofk_union_institution.institution_country) || ', '::text)
            ELSE ''::text
        END ||
        CASE
            WHEN (btrim(cofk_union_institution.institution_city) > ''::text) THEN (btrim(cofk_union_institution.institution_city) || ', '::text)
            ELSE ''::text
        END) ||
        CASE
            WHEN (btrim(cofk_union_institution.institution_name) > ''::text) THEN btrim(cofk_union_institution.institution_name)
            ELSE ''::text
        END) AS institution_name
   FROM public.cofk_union_institution;


ALTER TABLE public.cofk_union_institution_view OWNER TO postgres;

--
-- Name: cofk_union_language_of_manifestation; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_language_of_manifestation (
    manifestation_id character varying(100) NOT NULL,
    language_code character varying(3) NOT NULL,
    notes character varying(100)
);


ALTER TABLE public.cofk_union_language_of_manifestation OWNER TO postgres;

--
-- Name: cofk_union_language_of_work; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_language_of_work (
    work_id character varying(100) NOT NULL,
    language_code character varying(3) NOT NULL,
    notes character varying(100)
);


ALTER TABLE public.cofk_union_language_of_work OWNER TO postgres;

--
-- Name: cofk_union_language_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_language_view AS
 SELECT iso.code_639_3,
    iso.code_639_1,
    iso.language_name,
    ( SELECT 'Yes'::character varying AS "varchar"
           FROM public.cofk_union_favourite_language fav
          WHERE ((fav.language_code)::text = (iso.code_639_3)::text)) AS selected,
    iso.language_id
   FROM public.iso_639_language_codes iso;


ALTER TABLE public.cofk_union_language_view OWNER TO postgres;

--
-- Name: cofk_union_location_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_location_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_location_id_seq OWNER TO postgres;

--
-- Name: cofk_union_location; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_location (
    location_id integer DEFAULT nextval('public.cofk_union_location_id_seq'::regclass) NOT NULL,
    location_name character varying(500) DEFAULT ''::character varying NOT NULL,
    latitude character varying(20),
    longitude character varying(20),
    creation_timestamp timestamp without time zone DEFAULT now(),
    creation_user character varying(50) DEFAULT "current_user"() NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL,
    location_synonyms text,
    editors_notes text,
    element_1_eg_room character varying(100) DEFAULT ''::character varying NOT NULL,
    element_2_eg_building character varying(100) DEFAULT ''::character varying NOT NULL,
    element_3_eg_parish character varying(100) DEFAULT ''::character varying NOT NULL,
    element_4_eg_city character varying(100) DEFAULT ''::character varying NOT NULL,
    element_5_eg_county character varying(100) DEFAULT ''::character varying NOT NULL,
    element_6_eg_country character varying(100) DEFAULT ''::character varying NOT NULL,
    element_7_eg_empire character varying(100) DEFAULT ''::character varying NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4()
);


ALTER TABLE public.cofk_union_location OWNER TO postgres;

--
-- Name: cofk_union_relationship_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_relationship_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_relationship_id_seq OWNER TO postgres;

--
-- Name: cofk_union_relationship; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_relationship (
    relationship_id integer DEFAULT nextval('public.cofk_union_relationship_id_seq'::regclass) NOT NULL,
    left_table_name character varying(100) NOT NULL,
    left_id_value character varying(100) NOT NULL,
    relationship_type character varying(100) NOT NULL,
    right_table_name character varying(100) NOT NULL,
    right_id_value character varying(100) NOT NULL,
    relationship_valid_from timestamp without time zone,
    relationship_valid_till timestamp without time zone,
    creation_timestamp timestamp without time zone DEFAULT now(),
    creation_user character varying(50) DEFAULT "current_user"() NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL
);


ALTER TABLE public.cofk_union_relationship OWNER TO postgres;

--
-- Name: cofk_union_location_recd_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_location_recd_view AS
 SELECT DISTINCT r.right_id_value AS location_id,
    w.work_id,
    w.iwork_id,
    w.description
   FROM public.cofk_union_relationship r,
    public.cofk_union_work w
  WHERE (((r.left_table_name)::text = 'cofk_union_work'::text) AND ((r.relationship_type)::text = 'was_sent_to'::text) AND ((r.right_table_name)::text = 'cofk_union_location'::text) AND ((r.left_id_value)::text = (w.work_id)::text) AND ((w.date_of_work_std)::text <> '1900-01-01'::text) AND (w.work_to_be_deleted <> 1));


ALTER TABLE public.cofk_union_location_recd_view OWNER TO postgres;

--
-- Name: cofk_union_location_sent_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_location_sent_view AS
 SELECT DISTINCT r.right_id_value AS location_id,
    w.work_id,
    w.iwork_id,
    w.description
   FROM public.cofk_union_relationship r,
    public.cofk_union_work w
  WHERE (((r.left_table_name)::text = 'cofk_union_work'::text) AND ((r.relationship_type)::text = 'was_sent_from'::text) AND ((r.right_table_name)::text = 'cofk_union_location'::text) AND ((r.left_id_value)::text = (w.work_id)::text) AND ((w.date_of_work_std)::text <> '1900-01-01'::text) AND (w.work_to_be_deleted <> 1));


ALTER TABLE public.cofk_union_location_sent_view OWNER TO postgres;

--
-- Name: cofk_union_location_all_works_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_location_all_works_view AS
 SELECT cofk_union_location_sent_view.location_id,
    cofk_union_location_sent_view.work_id,
    cofk_union_location_sent_view.iwork_id,
    cofk_union_location_sent_view.description
   FROM public.cofk_union_location_sent_view
UNION
 SELECT cofk_union_location_recd_view.location_id,
    cofk_union_location_recd_view.work_id,
    cofk_union_location_recd_view.iwork_id,
    cofk_union_location_recd_view.description
   FROM public.cofk_union_location_recd_view;


ALTER TABLE public.cofk_union_location_all_works_view OWNER TO postgres;

--
-- Name: cofk_union_location_mentioned_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_location_mentioned_view AS
 SELECT DISTINCT r.right_id_value AS location_id,
    w.work_id,
    w.iwork_id,
    w.description
   FROM public.cofk_union_relationship r,
    public.cofk_union_work w
  WHERE (((r.right_table_name)::text = 'cofk_union_location'::text) AND ((r.left_table_name)::text = 'cofk_union_work'::text) AND ((r.relationship_type)::text = 'mentions_place'::text) AND ((r.left_id_value)::text = (w.work_id)::text) AND ((w.date_of_work_std)::text <> '1900-01-01'::text) AND (w.work_to_be_deleted <> 1));


ALTER TABLE public.cofk_union_location_mentioned_view OWNER TO postgres;

--
-- Name: cofk_union_location_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_location_view AS
 SELECT cofk_union_location.location_id,
    ((cofk_union_location.location_name)::text ||
        CASE
            WHEN (cofk_union_location.location_synonyms > ''::text) THEN ('
Alternative names:
'::text || cofk_union_location.location_synonyms)
            ELSE ''::text
        END) AS location_name,
    cofk_union_location.editors_notes,
    COALESCE(( SELECT count(*) AS count
           FROM public.cofk_union_location_sent_view vs
          WHERE ((vs.location_id)::text = ((cofk_union_location.location_id)::character varying)::text)), (0)::bigint) AS sent,
    COALESCE(( SELECT count(*) AS count
           FROM public.cofk_union_location_recd_view vr
          WHERE ((vr.location_id)::text = ((cofk_union_location.location_id)::character varying)::text)), (0)::bigint) AS recd,
    COALESCE(( SELECT count(*) AS count
           FROM public.cofk_union_location_all_works_view va
          WHERE ((va.location_id)::text = ((cofk_union_location.location_id)::character varying)::text)), (0)::bigint) AS all_works,
    public.dbf_cofk_union_list_rels_decoded('cofk_union_comment'::character varying, 'refers_to'::character varying, 'cofk_union_location'::character varying, (cofk_union_location.location_id)::character varying, 0) AS researchers_notes,
    public.dbf_cofk_union_list_related_resources('cofk_union_location'::character varying, (cofk_union_location.location_id)::character varying, 0) AS related_resources,
    cofk_union_location.latitude,
    cofk_union_location.longitude,
    cofk_union_location.element_1_eg_room,
    cofk_union_location.element_2_eg_building,
    cofk_union_location.element_3_eg_parish,
    cofk_union_location.element_4_eg_city,
    cofk_union_location.element_5_eg_county,
    cofk_union_location.element_6_eg_country,
    cofk_union_location.element_7_eg_empire,
    public.dbf_cofk_union_list_images_of_entity('cofk_union_location'::character varying, (cofk_union_location.location_id)::character varying) AS images,
    cofk_union_location.change_timestamp,
    cofk_union_location.change_user
   FROM public.cofk_union_location;


ALTER TABLE public.cofk_union_location_view OWNER TO postgres;

--
-- Name: cofk_union_manifestation; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_manifestation (
    manifestation_id character varying(100) NOT NULL,
    manifestation_type character varying(3) DEFAULT ''::character varying NOT NULL,
    id_number_or_shelfmark character varying(500),
    printed_edition_details text,
    paper_size character varying(500),
    paper_type_or_watermark character varying(500),
    number_of_pages_of_document integer,
    number_of_pages_of_text integer,
    seal character varying(500),
    postage_marks character varying(500),
    endorsements text,
    non_letter_enclosures text,
    manifestation_creation_calendar character varying(2) DEFAULT 'U'::character varying NOT NULL,
    manifestation_creation_date date,
    manifestation_creation_date_gregorian date,
    manifestation_creation_date_year integer,
    manifestation_creation_date_month integer,
    manifestation_creation_date_day integer,
    manifestation_creation_date_inferred smallint DEFAULT 0 NOT NULL,
    manifestation_creation_date_uncertain smallint DEFAULT 0 NOT NULL,
    manifestation_creation_date_approx smallint DEFAULT 0 NOT NULL,
    manifestation_is_translation smallint DEFAULT 0 NOT NULL,
    language_of_manifestation character varying(255),
    address text,
    manifestation_incipit text,
    manifestation_excipit text,
    manifestation_ps text,
    creation_timestamp timestamp without time zone DEFAULT now(),
    creation_user character varying(50) DEFAULT "current_user"() NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL,
    manifestation_creation_date2_year integer,
    manifestation_creation_date2_month integer,
    manifestation_creation_date2_day integer,
    manifestation_creation_date_is_range smallint DEFAULT 0 NOT NULL,
    manifestation_creation_date_as_marked character varying(250),
    opened character varying(3) DEFAULT 'o'::character varying NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4(),
    routing_mark_stamp text,
    routing_mark_ms text,
    handling_instructions text,
    stored_folded character varying(20),
    postage_costs_as_marked character varying(500),
    postage_costs character varying(500),
    non_delivery_reason character varying(500),
    date_of_receipt_as_marked character varying(500),
    manifestation_receipt_calendar character varying(2) DEFAULT 'U'::character varying NOT NULL,
    manifestation_receipt_date date,
    manifestation_receipt_date_gregorian date,
    manifestation_receipt_date_year integer,
    manifestation_receipt_date_month integer,
    manifestation_receipt_date_day integer,
    manifestation_receipt_date_inferred smallint DEFAULT 0 NOT NULL,
    manifestation_receipt_date_uncertain smallint DEFAULT 0 NOT NULL,
    manifestation_receipt_date_approx smallint DEFAULT 0 NOT NULL,
    manifestation_receipt_date2_year integer,
    manifestation_receipt_date2_month integer,
    manifestation_receipt_date2_day integer,
    manifestation_receipt_date_is_range smallint DEFAULT 0 NOT NULL,
    accompaniments text,
    CONSTRAINT cofk_chk_union_manifestation_creation_date_approx CHECK (((manifestation_creation_date_approx = 0) OR (manifestation_creation_date_approx = 1))),
    CONSTRAINT cofk_chk_union_manifestation_creation_date_inferred CHECK (((manifestation_creation_date_inferred = 0) OR (manifestation_creation_date_inferred = 1))),
    CONSTRAINT cofk_chk_union_manifestation_creation_date_uncertain CHECK (((manifestation_creation_date_uncertain = 0) OR (manifestation_creation_date_uncertain = 1)))
);


ALTER TABLE public.cofk_union_manifestation OWNER TO postgres;

--
-- Name: cofk_union_manifestation_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_manifestation_view AS
 SELECT rel.relationship_id,
    wk.work_id,
    wk.iwork_id,
    wk.date_of_work_std,
    wk.description,
    man.manifestation_id,
    ( SELECT cofk_lookup_document_type.document_type_desc
           FROM public.cofk_lookup_document_type
          WHERE ((cofk_lookup_document_type.document_type_code)::text = (man.manifestation_type)::text)) AS manifestation_type,
    man.id_number_or_shelfmark,
    man.printed_edition_details
   FROM public.cofk_union_queryable_work wk,
    public.cofk_union_manifestation man,
    public.cofk_union_relationship rel
  WHERE (((rel.left_table_name)::text = 'cofk_union_manifestation'::text) AND ((rel.left_id_value)::text = (man.manifestation_id)::text) AND ((rel.relationship_type)::text = 'is_manifestation_of'::text) AND ((rel.right_table_name)::text = 'cofk_union_work'::text) AND ((rel.right_id_value)::text = (wk.work_id)::text))
  ORDER BY wk.date_of_work_std, wk.work_id, man.manifestation_id;


ALTER TABLE public.cofk_union_manifestation_view OWNER TO postgres;

--
-- Name: cofk_union_manifestation_selection_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_manifestation_selection_view AS
 SELECT cofk_union_manifestation_view.relationship_id,
    cofk_union_manifestation_view.iwork_id,
    cofk_union_manifestation_view.manifestation_id,
    cofk_union_manifestation_view.date_of_work_std,
    cofk_union_manifestation_view.description,
    cofk_union_manifestation_view.manifestation_type,
    cofk_union_manifestation_view.id_number_or_shelfmark
   FROM public.cofk_union_manifestation_view
  WHERE (((cofk_union_manifestation_view.printed_edition_details IS NULL) OR (btrim(cofk_union_manifestation_view.printed_edition_details) = ''::text)) AND (cofk_union_manifestation_view.date_of_work_std <> '2000-01-01'::date) AND (cofk_union_manifestation_view.date_of_work_std <> '1900-01-01'::date))
  ORDER BY cofk_union_manifestation_view.date_of_work_std, cofk_union_manifestation_view.manifestation_id;


ALTER TABLE public.cofk_union_manifestation_selection_view OWNER TO postgres;

--
-- Name: cofk_union_modern_work_selection_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_modern_work_selection_view AS
 SELECT cofk_union_work.iwork_id,
    cofk_union_work.work_id,
    cofk_union_work.description
   FROM public.cofk_union_work
  WHERE (((cofk_union_work.date_of_work_std)::text = '1900-01-01'::text) OR (((cofk_union_work.date_of_work_std)::text >= '2000-01-01'::text) AND ((cofk_union_work.date_of_work_std)::text < '9999-01-01'::text)))
  ORDER BY cofk_union_work.description, cofk_union_work.work_id;


ALTER TABLE public.cofk_union_modern_work_selection_view OWNER TO postgres;

--
-- Name: cofk_union_nationality_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_nationality_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_nationality_id_seq OWNER TO postgres;

--
-- Name: cofk_union_nationality; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_nationality (
    nationality_id integer DEFAULT nextval('public.cofk_union_nationality_id_seq'::regclass) NOT NULL,
    nationality_desc character varying(100) DEFAULT ''::character varying NOT NULL
);


ALTER TABLE public.cofk_union_nationality OWNER TO postgres;

--
-- Name: cofk_union_org_type_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_org_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_org_type_id_seq OWNER TO postgres;

--
-- Name: cofk_union_org_type; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_org_type (
    org_type_id integer DEFAULT nextval('public.cofk_union_org_type_id_seq'::regclass) NOT NULL,
    org_type_desc character varying(100) DEFAULT ''::character varying NOT NULL
);


ALTER TABLE public.cofk_union_org_type OWNER TO postgres;

--
-- Name: cofk_union_organisation_view_from_table; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_organisation_view_from_table AS
 SELECT cofk_union_person.person_id,
    cofk_union_person.foaf_name,
    cofk_union_person.skos_altlabel,
    cofk_union_person.skos_hiddenlabel,
    cofk_union_person.person_aliases,
    cofk_union_person.date_of_birth_year,
    cofk_union_person.date_of_birth_month,
    cofk_union_person.date_of_birth_day,
    cofk_union_person.date_of_birth,
    cofk_union_person.date_of_birth_inferred,
    cofk_union_person.date_of_birth_uncertain,
    cofk_union_person.date_of_birth_approx,
    cofk_union_person.date_of_death_year,
    cofk_union_person.date_of_death_month,
    cofk_union_person.date_of_death_day,
    cofk_union_person.date_of_death,
    cofk_union_person.date_of_death_inferred,
    cofk_union_person.date_of_death_uncertain,
    cofk_union_person.date_of_death_approx,
    cofk_union_person.gender,
    cofk_union_person.is_organisation,
    cofk_union_person.iperson_id,
    cofk_union_person.creation_timestamp,
    cofk_union_person.creation_user,
    cofk_union_person.change_timestamp,
    cofk_union_person.change_user,
    cofk_union_person.editors_notes,
    cofk_union_person.further_reading,
    cofk_union_person.organisation_type,
    cofk_union_person.date_of_birth_calendar,
    cofk_union_person.date_of_birth_is_range,
    cofk_union_person.date_of_birth2_year,
    cofk_union_person.date_of_birth2_month,
    cofk_union_person.date_of_birth2_day,
    cofk_union_person.date_of_death_calendar,
    cofk_union_person.date_of_death_is_range,
    cofk_union_person.date_of_death2_year,
    cofk_union_person.date_of_death2_month,
    cofk_union_person.date_of_death2_day,
    cofk_union_person.flourished,
    cofk_union_person.flourished_calendar,
    cofk_union_person.flourished_is_range,
    cofk_union_person.flourished_year,
    cofk_union_person.flourished_month,
    cofk_union_person.flourished_day,
    cofk_union_person.flourished2_year,
    cofk_union_person.flourished2_month,
    cofk_union_person.flourished2_day
   FROM public.cofk_union_person
  WHERE ((cofk_union_person.is_organisation)::text = 'Y'::text);


ALTER TABLE public.cofk_union_organisation_view_from_table OWNER TO postgres;

--
-- Name: cofk_union_person_summary; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_person_summary (
    iperson_id integer NOT NULL,
    other_details_summary text,
    other_details_summary_searchable text,
    sent integer DEFAULT 0 NOT NULL,
    recd integer DEFAULT 0 NOT NULL,
    all_works integer DEFAULT 0 NOT NULL,
    mentioned integer DEFAULT 0 NOT NULL,
    role_categories text,
    images text
);


ALTER TABLE public.cofk_union_person_summary OWNER TO postgres;

--
-- Name: cofk_union_person_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_person_view AS
 SELECT p.person_id,
    ((((p.foaf_name)::text ||
        CASE
            WHEN (p.skos_altlabel > ''::text) THEN (' ~ Synonyms: '::text || p.skos_altlabel)
            ELSE ''::text
        END) ||
        CASE
            WHEN (p.person_aliases > ''::text) THEN (' ~ Titles/roles: '::text || p.person_aliases)
            ELSE ''::text
        END) ||
        CASE
            WHEN (summ.role_categories > ''::text) THEN (' ~ Role types: '::text || summ.role_categories)
            ELSE ''::text
        END) AS names_and_titles,
    p.date_of_birth,
    p.date_of_birth_is_range AS date_of_birth_estimated_range,
        CASE
            WHEN ((p.date_of_birth IS NOT NULL) AND (p.date_of_birth_is_range = 0)) THEN p.date_of_birth
            WHEN ((p.date_of_birth_year IS NOT NULL) AND (p.date_of_birth_is_range = 1)) THEN ((((p.date_of_birth_year)::character varying)::text || '-01-01'::text))::date
            ELSE NULL::date
        END AS date_of_birth_from,
        CASE
            WHEN ((p.date_of_birth IS NOT NULL) AND (p.date_of_birth_is_range = 0)) THEN p.date_of_birth
            WHEN ((p.date_of_birth2_year IS NOT NULL) AND (p.date_of_birth_is_range = 1)) THEN ((((p.date_of_birth2_year)::character varying)::text || '-12-31'::text))::date
            ELSE NULL::date
        END AS date_of_birth_to,
    p.date_of_death,
    p.date_of_death_is_range AS date_of_death_estimated_range,
        CASE
            WHEN ((p.date_of_death IS NOT NULL) AND (p.date_of_death_is_range = 0)) THEN p.date_of_death
            WHEN ((p.date_of_death_year IS NOT NULL) AND (p.date_of_death_is_range = 1)) THEN ((((p.date_of_death_year)::character varying)::text || '-01-01'::text))::date
            ELSE NULL::date
        END AS date_of_death_from,
        CASE
            WHEN ((p.date_of_death IS NOT NULL) AND (p.date_of_death_is_range = 0)) THEN p.date_of_death
            WHEN ((p.date_of_death2_year IS NOT NULL) AND (p.date_of_death_is_range = 1)) THEN ((((p.date_of_death2_year)::character varying)::text || '-12-31'::text))::date
            ELSE NULL::date
        END AS date_of_death_to,
    p.flourished,
    p.flourished_is_range AS flourished_estimated_range,
        CASE
            WHEN ((p.flourished IS NOT NULL) AND (p.flourished_is_range = 0)) THEN p.flourished
            WHEN ((p.flourished_year IS NOT NULL) AND (p.flourished_is_range = 1)) THEN ((((p.flourished_year)::character varying)::text || '-01-01'::text))::date
            ELSE NULL::date
        END AS flourished_from,
        CASE
            WHEN ((p.flourished IS NOT NULL) AND (p.flourished_is_range = 0)) THEN p.flourished
            WHEN ((p.flourished2_year IS NOT NULL) AND (p.flourished_is_range = 1)) THEN ((((p.flourished2_year)::character varying)::text || '-12-31'::text))::date
            ELSE NULL::date
        END AS flourished_to,
    p.gender,
        CASE p.is_organisation
            WHEN 'Y'::text THEN 'Org'::text
            ELSE ''::text
        END AS is_organisation,
    ( SELECT cofk_union_org_type.org_type_desc
           FROM public.cofk_union_org_type
          WHERE (p.organisation_type = cofk_union_org_type.org_type_id)) AS org_type,
    summ.sent,
    summ.recd,
    summ.all_works,
    summ.mentioned,
    p.iperson_id,
    p.editors_notes,
    p.further_reading,
    summ.images,
    summ.other_details_summary,
    summ.other_details_summary_searchable,
    p.change_timestamp,
    p.change_user
   FROM public.cofk_union_person p,
    public.cofk_union_person_summary summ
  WHERE (p.iperson_id = summ.iperson_id);


ALTER TABLE public.cofk_union_person_view OWNER TO postgres;

--
-- Name: cofk_union_organisation_view_from_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_organisation_view_from_view AS
 SELECT cofk_union_person_view.person_id,
    cofk_union_person_view.names_and_titles,
    cofk_union_person_view.date_of_birth,
    cofk_union_person_view.date_of_birth_estimated_range,
    cofk_union_person_view.date_of_birth_from,
    cofk_union_person_view.date_of_birth_to,
    cofk_union_person_view.date_of_death,
    cofk_union_person_view.date_of_death_estimated_range,
    cofk_union_person_view.date_of_death_from,
    cofk_union_person_view.date_of_death_to,
    cofk_union_person_view.flourished,
    cofk_union_person_view.flourished_estimated_range,
    cofk_union_person_view.flourished_from,
    cofk_union_person_view.flourished_to,
    cofk_union_person_view.gender,
    cofk_union_person_view.is_organisation,
    cofk_union_person_view.org_type,
    cofk_union_person_view.sent,
    cofk_union_person_view.recd,
    cofk_union_person_view.all_works,
    cofk_union_person_view.mentioned,
    cofk_union_person_view.iperson_id,
    cofk_union_person_view.editors_notes,
    cofk_union_person_view.further_reading,
    cofk_union_person_view.images,
    cofk_union_person_view.other_details_summary,
    cofk_union_person_view.other_details_summary_searchable,
    cofk_union_person_view.change_timestamp,
    cofk_union_person_view.change_user
   FROM public.cofk_union_person_view
  WHERE (cofk_union_person_view.is_organisation = 'Org'::text);


ALTER TABLE public.cofk_union_organisation_view_from_view OWNER TO postgres;

--
-- Name: cofk_union_person_recd_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_person_recd_view AS
 SELECT DISTINCT r.right_id_value AS person_id,
    w.work_id,
    w.iwork_id,
    w.description
   FROM public.cofk_union_relationship r,
    public.cofk_union_work w
  WHERE (((r.right_table_name)::text = 'cofk_union_person'::text) AND ((r.left_table_name)::text = 'cofk_union_work'::text) AND ((r.relationship_type)::text = 'was_addressed_to'::text) AND ((r.left_id_value)::text = (w.work_id)::text) AND ((w.date_of_work_std)::text <> '1900-01-01'::text) AND (w.work_to_be_deleted <> 1));


ALTER TABLE public.cofk_union_person_recd_view OWNER TO postgres;

--
-- Name: cofk_union_person_sent_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_person_sent_view AS
 SELECT DISTINCT r.left_id_value AS person_id,
    w.work_id,
    w.iwork_id,
    w.description
   FROM public.cofk_union_relationship r,
    public.cofk_union_work w
  WHERE (((r.left_table_name)::text = 'cofk_union_person'::text) AND ((r.right_table_name)::text = 'cofk_union_work'::text) AND ((r.relationship_type)::text = ANY (ARRAY[('created'::character varying)::text, ('sent'::character varying)::text, ('signed'::character varying)::text])) AND ((r.right_id_value)::text = (w.work_id)::text) AND ((w.date_of_work_std)::text <> '1900-01-01'::text) AND (w.work_to_be_deleted <> 1));


ALTER TABLE public.cofk_union_person_sent_view OWNER TO postgres;

--
-- Name: cofk_union_person_all_works_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_person_all_works_view AS
 SELECT cofk_union_person_recd_view.person_id,
    cofk_union_person_recd_view.work_id,
    cofk_union_person_recd_view.iwork_id,
    cofk_union_person_recd_view.description
   FROM public.cofk_union_person_recd_view
UNION
 SELECT cofk_union_person_sent_view.person_id,
    cofk_union_person_sent_view.work_id,
    cofk_union_person_sent_view.iwork_id,
    cofk_union_person_sent_view.description
   FROM public.cofk_union_person_sent_view;


ALTER TABLE public.cofk_union_person_all_works_view OWNER TO postgres;

--
-- Name: cofk_union_person_mentioned_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_person_mentioned_view AS
 SELECT DISTINCT r.right_id_value AS person_id,
    w.work_id,
    w.iwork_id,
    w.description
   FROM public.cofk_union_relationship r,
    public.cofk_union_work w
  WHERE (((r.right_table_name)::text = 'cofk_union_person'::text) AND ((r.left_table_name)::text = 'cofk_union_work'::text) AND ((r.relationship_type)::text = 'mentions'::text) AND ((r.left_id_value)::text = (w.work_id)::text) AND ((w.date_of_work_std)::text <> '1900-01-01'::text) AND (w.work_to_be_deleted <> 1));


ALTER TABLE public.cofk_union_person_mentioned_view OWNER TO postgres;

--
-- Name: cofk_union_publication_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_publication_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_publication_id_seq OWNER TO postgres;

--
-- Name: cofk_union_publication; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_publication (
    publication_id integer DEFAULT nextval('public.cofk_union_publication_id_seq'::regclass) NOT NULL,
    publication_details text DEFAULT ''::text NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL,
    abbrev character varying(50) DEFAULT ''::character varying NOT NULL
);


ALTER TABLE public.cofk_union_publication OWNER TO postgres;

--
-- Name: cofk_union_relationship_type; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_relationship_type (
    relationship_code character varying(50) NOT NULL,
    desc_left_to_right character varying(200) DEFAULT ''::character varying NOT NULL,
    desc_right_to_left character varying(200) DEFAULT ''::character varying NOT NULL,
    creation_timestamp timestamp without time zone DEFAULT now(),
    creation_user character varying(50) DEFAULT "current_user"() NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL
);


ALTER TABLE public.cofk_union_relationship_type OWNER TO postgres;

--
-- Name: cofk_union_resource_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_resource_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_resource_id_seq OWNER TO postgres;

--
-- Name: cofk_union_resource; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_resource (
    resource_id integer DEFAULT nextval('public.cofk_union_resource_id_seq'::regclass) NOT NULL,
    resource_name text DEFAULT ''::text NOT NULL,
    resource_details text DEFAULT ''::text NOT NULL,
    resource_url text DEFAULT ''::text NOT NULL,
    creation_timestamp timestamp without time zone DEFAULT now(),
    creation_user character varying(50) DEFAULT "current_user"() NOT NULL,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user character varying(50) DEFAULT "current_user"() NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4()
);


ALTER TABLE public.cofk_union_resource OWNER TO postgres;

--
-- Name: cofk_union_role_category_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_role_category_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_role_category_id_seq OWNER TO postgres;

--
-- Name: cofk_union_role_category; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_role_category (
    role_category_id integer DEFAULT nextval('public.cofk_union_role_category_id_seq'::regclass) NOT NULL,
    role_category_desc character varying(100) DEFAULT ''::character varying NOT NULL
);


ALTER TABLE public.cofk_union_role_category OWNER TO postgres;

--
-- Name: cofk_union_speed_entry_text_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_speed_entry_text_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_speed_entry_text_id_seq OWNER TO postgres;

--
-- Name: cofk_union_speed_entry_text; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_speed_entry_text (
    speed_entry_text_id integer DEFAULT nextval('public.cofk_union_speed_entry_text_id_seq'::regclass) NOT NULL,
    object_type character varying(30) DEFAULT 'All'::character varying NOT NULL,
    speed_entry_text character varying(200) DEFAULT ''::character varying NOT NULL
);


ALTER TABLE public.cofk_union_speed_entry_text OWNER TO postgres;

--
-- Name: cofk_union_subject_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_union_subject_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_union_subject_id_seq OWNER TO postgres;

--
-- Name: cofk_union_subject; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_union_subject (
    subject_id integer DEFAULT nextval('public.cofk_union_subject_id_seq'::regclass) NOT NULL,
    subject_desc character varying(100) DEFAULT ''::character varying NOT NULL
);


ALTER TABLE public.cofk_union_subject OWNER TO postgres;

--
-- Name: cofk_union_work_image_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_work_image_view AS
 SELECT cofk_union_work_view.description,
    cofk_union_work_view.images AS enlarged_images,
    cofk_union_work_view.iwork_id
   FROM public.cofk_union_work_view;


ALTER TABLE public.cofk_union_work_image_view OWNER TO postgres;

--
-- Name: cofk_union_work_selection_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_union_work_selection_view AS
 SELECT cofk_union_queryable_work.iwork_id,
    cofk_union_queryable_work.work_id,
    cofk_union_queryable_work.date_of_work_std,
    cofk_union_queryable_work.description
   FROM public.cofk_union_queryable_work
  WHERE (cofk_union_queryable_work.date_of_work_std <> '1900-01-01'::date)
  ORDER BY cofk_union_queryable_work.date_of_work_std, cofk_union_queryable_work.work_id;


ALTER TABLE public.cofk_union_work_selection_view OWNER TO postgres;

--
-- Name: cofk_user_roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_user_roles (
    username character varying(30) NOT NULL,
    role_id integer NOT NULL
);


ALTER TABLE public.cofk_user_roles OWNER TO postgres;

--
-- Name: cofk_user_saved_queries_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_user_saved_queries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_user_saved_queries_id_seq OWNER TO postgres;

--
-- Name: cofk_user_saved_queries; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_user_saved_queries (
    query_id integer DEFAULT nextval('public.cofk_user_saved_queries_id_seq'::regclass) NOT NULL,
    username character varying(30) DEFAULT "current_user"() NOT NULL,
    query_class character varying(100) NOT NULL,
    query_method character varying(100) NOT NULL,
    query_title text DEFAULT ''::text NOT NULL,
    query_order_by character varying(100) DEFAULT ''::character varying NOT NULL,
    query_sort_descending smallint DEFAULT 0 NOT NULL,
    query_entries_per_page smallint DEFAULT 20 NOT NULL,
    query_record_layout character varying(12) DEFAULT 'across_page'::character varying NOT NULL,
    query_menu_item_name text,
    creation_timestamp timestamp without time zone DEFAULT now()
);


ALTER TABLE public.cofk_user_saved_queries OWNER TO postgres;

--
-- Name: cofk_user_saved_query_selection_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_user_saved_query_selection_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_user_saved_query_selection_id_seq OWNER TO postgres;

--
-- Name: cofk_user_saved_query_selection; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_user_saved_query_selection (
    selection_id integer DEFAULT nextval('public.cofk_user_saved_query_selection_id_seq'::regclass) NOT NULL,
    query_id integer NOT NULL,
    column_name character varying(100) NOT NULL,
    column_value character varying(500) NOT NULL,
    op_name character varying(100) NOT NULL,
    op_value character varying(100) NOT NULL,
    column_value2 character varying(500) DEFAULT ''::character varying NOT NULL
);


ALTER TABLE public.cofk_user_saved_query_selection OWNER TO postgres;

--
-- Name: cofk_users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cofk_users (
    username character varying(30) NOT NULL,
    pw text NOT NULL,
    surname character varying(30) DEFAULT ''::character varying NOT NULL,
    forename character varying(30) DEFAULT ''::character varying NOT NULL,
    failed_logins integer DEFAULT 0 NOT NULL,
    login_time timestamp without time zone,
    prev_login timestamp without time zone,
    active smallint DEFAULT 1 NOT NULL,
    email text,
    CONSTRAINT cofk_users_active CHECK (((active = 0) OR (active = 1))),
    CONSTRAINT cofk_users_pw CHECK ((pw > ''::text))
);


ALTER TABLE public.cofk_users OWNER TO postgres;

--
-- Name: cofk_users_and_roles_view; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.cofk_users_and_roles_view AS
 SELECT u.username,
    u.surname,
    u.forename,
    u.active,
    u.email,
    r.role_id,
    r.role_code,
    r.role_name
   FROM public.cofk_users u,
    public.cofk_user_roles ur,
    public.cofk_roles r
  WHERE ((r.role_id = ur.role_id) AND ((ur.username)::text = (u.username)::text) AND (((u.username)::name = "current_user"()) OR ("current_user"() IN ( SELECT cofk_user_roles.username
           FROM public.cofk_user_roles
          WHERE (cofk_user_roles.role_id = '-1'::integer)))))
UNION
 SELECT u2.username,
    u2.surname,
    u2.forename,
    u2.active,
    u2.email,
    NULL::integer AS role_id,
    NULL::character varying AS role_code,
    NULL::text AS role_name
   FROM public.cofk_users u2
  WHERE ((NOT (EXISTS ( SELECT ur2.role_id
           FROM public.cofk_user_roles ur2
          WHERE ((u2.username)::text = (ur2.username)::text)))) AND (((u2.username)::name = "current_user"()) OR ("current_user"() IN ( SELECT cofk_user_roles.username
           FROM public.cofk_user_roles
          WHERE (cofk_user_roles.role_id = '-1'::integer)))))
  ORDER BY 1, 8;


ALTER TABLE public.cofk_users_and_roles_view OWNER TO postgres;

--
-- Name: cofk_users_username_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cofk_users_username_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cofk_users_username_seq OWNER TO postgres;

--
-- Name: copy_cofk_union_queryable_work; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.copy_cofk_union_queryable_work (
    iwork_id integer,
    work_id character varying(100),
    description text,
    date_of_work_std date,
    date_of_work_std_year integer,
    date_of_work_std_month integer,
    date_of_work_std_day integer,
    date_of_work_as_marked character varying(250),
    date_of_work_inferred smallint,
    date_of_work_uncertain smallint,
    date_of_work_approx smallint,
    creators_searchable text,
    creators_for_display text,
    authors_as_marked text,
    notes_on_authors text,
    authors_inferred smallint,
    authors_uncertain smallint,
    addressees_searchable text,
    addressees_for_display text,
    addressees_as_marked text,
    addressees_inferred smallint,
    addressees_uncertain smallint,
    places_from_searchable text,
    places_from_for_display text,
    origin_as_marked text,
    origin_inferred smallint,
    origin_uncertain smallint,
    places_to_searchable text,
    places_to_for_display text,
    destination_as_marked text,
    destination_inferred smallint,
    destination_uncertain smallint,
    manifestations_searchable text,
    manifestations_for_display text,
    abstract text,
    keywords text,
    people_mentioned text,
    images text,
    related_resources text,
    language_of_work character varying(255),
    work_is_translation smallint,
    flags text,
    edit_status character varying(3),
    general_notes text,
    original_catalogue character varying(100),
    accession_code character varying(1000),
    work_to_be_deleted smallint,
    change_timestamp timestamp without time zone,
    change_user character varying(50),
    drawer character varying(50),
    editors_notes text,
    manifestation_type character varying(50),
    original_notes text,
    relevant_to_cofk character varying(1),
    subjects text
);


ALTER TABLE public.copy_cofk_union_queryable_work OWNER TO postgres;

--
-- Name: pro_id_activity; Type: SEQUENCE; Schema: public; Owner: cofktanya
--

CREATE SEQUENCE public.pro_id_activity
    START WITH 45
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pro_id_activity OWNER TO cofktanya;

--
-- Name: pro_activity; Type: TABLE; Schema: public; Owner: cofktanya
--

CREATE TABLE public.pro_activity (
    id integer DEFAULT nextval('public.pro_id_activity'::regclass) NOT NULL,
    activity_type_id text,
    activity_name text,
    activity_description text,
    date_type text,
    date_from_year text,
    date_from_month text,
    date_from_day text,
    date_from_uncertainty text,
    date_to_year text,
    date_to_month text,
    date_to_day text,
    date_to_uncertainty text,
    notes_used text,
    additional_notes text,
    creation_timestamp timestamp without time zone DEFAULT now(),
    creation_user text,
    change_timestamp timestamp without time zone DEFAULT now(),
    change_user text,
    event_label text
);


ALTER TABLE public.pro_activity OWNER TO cofktanya;

--
-- Name: TABLE pro_activity; Type: COMMENT; Schema: public; Owner: cofktanya
--

COMMENT ON TABLE public.pro_activity IS 'prosopographical activity';


--
-- Name: pro_id_activity_relation; Type: SEQUENCE; Schema: public; Owner: cofktanya
--

CREATE SEQUENCE public.pro_id_activity_relation
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pro_id_activity_relation OWNER TO cofktanya;

--
-- Name: pro_activity_relation; Type: TABLE; Schema: public; Owner: cofktanya
--

CREATE TABLE public.pro_activity_relation (
    id integer DEFAULT nextval('public.pro_id_activity_relation'::regclass) NOT NULL,
    meta_activity_id integer,
    filename text NOT NULL,
    spreadsheet_row integer NOT NULL,
    combined_spreadsheet_row integer NOT NULL
);


ALTER TABLE public.pro_activity_relation OWNER TO cofktanya;

--
-- Name: TABLE pro_activity_relation; Type: COMMENT; Schema: public; Owner: cofktanya
--

COMMENT ON TABLE public.pro_activity_relation IS 'mapping for related prosopography events';


--
-- Name: pro_id_assertion; Type: SEQUENCE; Schema: public; Owner: cofktanya
--

CREATE SEQUENCE public.pro_id_assertion
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pro_id_assertion OWNER TO cofktanya;

--
-- Name: pro_assertion; Type: TABLE; Schema: public; Owner: cofktanya
--

CREATE TABLE public.pro_assertion (
    id integer DEFAULT nextval('public.pro_id_assertion'::regclass) NOT NULL,
    assertion_type text,
    assertion_id text,
    source_id text,
    source_description text,
    change_timestamp timestamp without time zone DEFAULT now()
);


ALTER TABLE public.pro_assertion OWNER TO cofktanya;

--
-- Name: pro_id_location; Type: SEQUENCE; Schema: public; Owner: cofktanya
--

CREATE SEQUENCE public.pro_id_location
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pro_id_location OWNER TO cofktanya;

--
-- Name: pro_id_primary_person; Type: SEQUENCE; Schema: public; Owner: cofktanya
--

CREATE SEQUENCE public.pro_id_primary_person
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pro_id_primary_person OWNER TO cofktanya;

--
-- Name: pro_id_relationship; Type: SEQUENCE; Schema: public; Owner: cofktanya
--

CREATE SEQUENCE public.pro_id_relationship
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pro_id_relationship OWNER TO cofktanya;

--
-- Name: pro_id_role_in_activity; Type: SEQUENCE; Schema: public; Owner: cofktanya
--

CREATE SEQUENCE public.pro_id_role_in_activity
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pro_id_role_in_activity OWNER TO cofktanya;

--
-- Name: pro_id_textual_source; Type: SEQUENCE; Schema: public; Owner: cofktanya
--

CREATE SEQUENCE public.pro_id_textual_source
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.pro_id_textual_source OWNER TO cofktanya;

--
-- Name: pro_ingest_map_v2; Type: TABLE; Schema: public; Owner: cofktanya
--

CREATE TABLE public.pro_ingest_map_v2 (
    relationship text,
    mapping text,
    s_event_category text,
    s_event_type text,
    s_role text,
    p_event_category text,
    p_event_type text,
    p_role text
);


ALTER TABLE public.pro_ingest_map_v2 OWNER TO cofktanya;

--
-- Name: pro_ingest_v8; Type: TABLE; Schema: public; Owner: cofktanya
--

CREATE TABLE public.pro_ingest_v8 (
    event_category text,
    event_type text,
    event_name text,
    pp_i text,
    pp_name text,
    pp_role text,
    sp_i text,
    sp_name text,
    sp_type text,
    sp_role text,
    df_year text,
    df_month text,
    df_day text,
    df_uncertainty text,
    dt_year text,
    dt_month text,
    dt_day text,
    dt_uncertainty text,
    date_type text,
    location_i text,
    location_detail text,
    location_city text,
    location_region text,
    location_country text,
    location_type text,
    ts_abbrev text,
    ts_detail text,
    editor text,
    noted_used text,
    add_notes text,
    filename text,
    spreadsheet_row_id text,
    combined_csv_row_id text
);


ALTER TABLE public.pro_ingest_v8 OWNER TO cofktanya;

--
-- Name: pro_ingest_v8_toreview; Type: TABLE; Schema: public; Owner: cofktanya
--

CREATE TABLE public.pro_ingest_v8_toreview (
    event_category text,
    event_type text,
    event_name text,
    pp_i text,
    pp_name text,
    pp_role text,
    sp_i text,
    sp_name text,
    sp_type text,
    sp_role text,
    df_year text,
    df_month text,
    df_day text,
    df_uncertainty text,
    dt_year text,
    dt_month text,
    dt_day text,
    dt_uncertainty text,
    date_type text,
    location_i text,
    location_detail text,
    location_city text,
    location_region text,
    location_country text,
    location_type text,
    ts_abbrev text,
    ts_detail text,
    editor text,
    noted_used text,
    add_notes text,
    filename text,
    spreadsheet_row_id text,
    combined_csv_row_id text
);


ALTER TABLE public.pro_ingest_v8_toreview OWNER TO cofktanya;

--
-- Name: pro_location; Type: TABLE; Schema: public; Owner: cofktanya
--

CREATE TABLE public.pro_location (
    id integer DEFAULT nextval('public.pro_id_location'::regclass) NOT NULL,
    location_id text,
    change_timestamp timestamp without time zone DEFAULT now(),
    activity_id integer
);


ALTER TABLE public.pro_location OWNER TO cofktanya;

--
-- Name: pro_people_check; Type: TABLE; Schema: public; Owner: cofktanya
--

CREATE TABLE public.pro_people_check (
    person_name text,
    iperson_id text
);


ALTER TABLE public.pro_people_check OWNER TO cofktanya;

--
-- Name: pro_primary_person; Type: TABLE; Schema: public; Owner: cofktanya
--

CREATE TABLE public.pro_primary_person (
    id integer DEFAULT nextval('public.pro_id_primary_person'::regclass) NOT NULL,
    person_id text,
    change_timestamp timestamp without time zone DEFAULT now(),
    activity_id integer
);


ALTER TABLE public.pro_primary_person OWNER TO cofktanya;

--
-- Name: pro_relationship; Type: TABLE; Schema: public; Owner: cofktanya
--

CREATE TABLE public.pro_relationship (
    id integer DEFAULT nextval('public.pro_id_relationship'::regclass) NOT NULL,
    subject_id text,
    subject_type text,
    subject_role_id text,
    relationship_id text,
    object_id text,
    object_type text,
    object_role_id text,
    change_timestamp timestamp without time zone DEFAULT now(),
    activity_id integer
);


ALTER TABLE public.pro_relationship OWNER TO cofktanya;

--
-- Name: pro_role_in_activity; Type: TABLE; Schema: public; Owner: cofktanya
--

CREATE TABLE public.pro_role_in_activity (
    id integer DEFAULT nextval('public.pro_id_role_in_activity'::regclass) NOT NULL,
    entity_type text,
    entity_id text,
    role_id text,
    change_timestamp timestamp without time zone DEFAULT now(),
    activity_id integer
);


ALTER TABLE public.pro_role_in_activity OWNER TO cofktanya;

--
-- Name: pro_textual_source; Type: TABLE; Schema: public; Owner: cofktanya
--

CREATE TABLE public.pro_textual_source (
    id integer DEFAULT nextval('public.pro_id_textual_source'::regclass) NOT NULL,
    author text,
    title text,
    "chapterArticleTitle" text,
    "volumeSeriesNumber" text,
    "issueNumber" text,
    "pageNumber" text,
    editor text,
    "placePublication" text,
    "datePublication" text,
    "urlResource" text,
    abbreviation text,
    "fullBibliographicDetails" text,
    edition text,
    "reprintFacsimile" text,
    repository text,
    creation_user text,
    creation_timestamp timestamp without time zone DEFAULT now(),
    change_user text,
    change_timestamp timestamp without time zone DEFAULT now()
);


ALTER TABLE public.pro_textual_source OWNER TO cofktanya;

--
-- Name: cofk_collect_addressee_of_work cofk_collect_addressee_of_work_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_addressee_of_work
    ADD CONSTRAINT cofk_collect_addressee_of_work_pkey PRIMARY KEY (upload_id, iwork_id, addressee_id);


--
-- Name: cofk_collect_author_of_work cofk_collect_author_of_work_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_author_of_work
    ADD CONSTRAINT cofk_collect_author_of_work_pkey PRIMARY KEY (upload_id, iwork_id, author_id);


--
-- Name: cofk_collect_destination_of_work cofk_collect_destination_of_work_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_destination_of_work
    ADD CONSTRAINT cofk_collect_destination_of_work_pkey PRIMARY KEY (upload_id, iwork_id, destination_id);


--
-- Name: cofk_collect_institution cofk_collect_institution_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_institution
    ADD CONSTRAINT cofk_collect_institution_pkey PRIMARY KEY (upload_id, institution_id);


--
-- Name: cofk_collect_institution_resource cofk_collect_institution_resource_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_institution_resource
    ADD CONSTRAINT cofk_collect_institution_resource_pkey PRIMARY KEY (upload_id, resource_id);


--
-- Name: cofk_collect_language_of_work cofk_collect_language_of_work_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_language_of_work
    ADD CONSTRAINT cofk_collect_language_of_work_pkey PRIMARY KEY (upload_id, iwork_id, language_of_work_id);


--
-- Name: cofk_collect_location cofk_collect_location_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_location
    ADD CONSTRAINT cofk_collect_location_pkey PRIMARY KEY (upload_id, location_id);


--
-- Name: cofk_collect_location_resource cofk_collect_location_resource_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_location_resource
    ADD CONSTRAINT cofk_collect_location_resource_pkey PRIMARY KEY (upload_id, resource_id);


--
-- Name: cofk_collect_manifestation cofk_collect_manifestation_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_manifestation
    ADD CONSTRAINT cofk_collect_manifestation_pkey PRIMARY KEY (upload_id, iwork_id, manifestation_id);


--
-- Name: cofk_collect_occupation_of_person cofk_collect_occupation_of_person_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_occupation_of_person
    ADD CONSTRAINT cofk_collect_occupation_of_person_pkey PRIMARY KEY (upload_id, occupation_of_person_id);


--
-- Name: cofk_collect_origin_of_work cofk_collect_origin_of_work_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_origin_of_work
    ADD CONSTRAINT cofk_collect_origin_of_work_pkey PRIMARY KEY (upload_id, iwork_id, origin_id);


--
-- Name: cofk_collect_person_mentioned_in_work cofk_collect_person_mentioned_in_work_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_person_mentioned_in_work
    ADD CONSTRAINT cofk_collect_person_mentioned_in_work_pkey PRIMARY KEY (upload_id, iwork_id, mention_id);


--
-- Name: cofk_collect_person cofk_collect_person_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_person
    ADD CONSTRAINT cofk_collect_person_pkey PRIMARY KEY (upload_id, iperson_id);


--
-- Name: cofk_collect_person_resource cofk_collect_person_resource_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_person_resource
    ADD CONSTRAINT cofk_collect_person_resource_pkey PRIMARY KEY (upload_id, resource_id);


--
-- Name: cofk_collect_place_mentioned_in_work cofk_collect_place_mentioned_in_work_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_place_mentioned_in_work
    ADD CONSTRAINT cofk_collect_place_mentioned_in_work_pkey PRIMARY KEY (upload_id, iwork_id, mention_id);


--
-- Name: cofk_collect_status cofk_collect_status_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_status
    ADD CONSTRAINT cofk_collect_status_pkey PRIMARY KEY (status_id);


--
-- Name: cofk_collect_subject_of_work cofk_collect_subject_of_work_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_subject_of_work
    ADD CONSTRAINT cofk_collect_subject_of_work_pkey PRIMARY KEY (upload_id, iwork_id, subject_of_work_id);


--
-- Name: cofk_collect_tool_session cofk_collect_tool_session_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_tool_session
    ADD CONSTRAINT cofk_collect_tool_session_pkey PRIMARY KEY (session_id);


--
-- Name: cofk_collect_tool_session cofk_collect_tool_uniq_session_code; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_tool_session
    ADD CONSTRAINT cofk_collect_tool_uniq_session_code UNIQUE (session_code);


--
-- Name: cofk_collect_tool_user cofk_collect_tool_user_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_tool_user
    ADD CONSTRAINT cofk_collect_tool_user_pkey PRIMARY KEY (tool_user_id);


--
-- Name: cofk_collect_tool_user cofk_collect_tool_user_uniq_email; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_tool_user
    ADD CONSTRAINT cofk_collect_tool_user_uniq_email UNIQUE (tool_user_email);


--
-- Name: cofk_collect_upload cofk_collect_upload_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_upload
    ADD CONSTRAINT cofk_collect_upload_pkey PRIMARY KEY (upload_id);


--
-- Name: cofk_collect_work cofk_collect_work_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_work
    ADD CONSTRAINT cofk_collect_work_pkey PRIMARY KEY (upload_id, iwork_id);


--
-- Name: cofk_collect_work_resource cofk_collect_work_resource_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_work_resource
    ADD CONSTRAINT cofk_collect_work_resource_pkey PRIMARY KEY (upload_id, iwork_id, resource_id);


--
-- Name: cofk_collect_work_summary cofk_collect_work_summary_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_work_summary
    ADD CONSTRAINT cofk_collect_work_summary_pkey PRIMARY KEY (upload_id, work_id_in_tool);


--
-- Name: cofk_help_options cofk_help_options_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_help_options
    ADD CONSTRAINT cofk_help_options_pkey PRIMARY KEY (option_id);


--
-- Name: cofk_help_pages cofk_help_pages_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_help_pages
    ADD CONSTRAINT cofk_help_pages_pkey PRIMARY KEY (page_id);


--
-- Name: cofk_lookup_catalogue cofk_lookup_catalogue_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_lookup_catalogue
    ADD CONSTRAINT cofk_lookup_catalogue_pkey PRIMARY KEY (catalogue_id);


--
-- Name: cofk_lookup_document_type cofk_lookup_document_type_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_lookup_document_type
    ADD CONSTRAINT cofk_lookup_document_type_pkey PRIMARY KEY (document_type_id);


--
-- Name: cofk_lookup_document_type cofk_lookup_uniq_document_type_code; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_lookup_document_type
    ADD CONSTRAINT cofk_lookup_uniq_document_type_code UNIQUE (document_type_code);


--
-- Name: cofk_menu cofk_menu_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_menu
    ADD CONSTRAINT cofk_menu_pkey PRIMARY KEY (menu_item_id);


--
-- Name: cofk_report_groups cofk_report_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_report_groups
    ADD CONSTRAINT cofk_report_groups_pkey PRIMARY KEY (report_group_id);


--
-- Name: cofk_reports cofk_reports_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_reports
    ADD CONSTRAINT cofk_reports_pkey PRIMARY KEY (report_id);


--
-- Name: cofk_roles cofk_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_roles
    ADD CONSTRAINT cofk_roles_pkey PRIMARY KEY (role_id);


--
-- Name: cofk_sessions cofk_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_sessions
    ADD CONSTRAINT cofk_sessions_pkey PRIMARY KEY (session_id);


--
-- Name: cofk_union_audit_literal cofk_union_audit_literal_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_audit_literal
    ADD CONSTRAINT cofk_union_audit_literal_pkey PRIMARY KEY (audit_id);


--
-- Name: cofk_union_audit_relationship cofk_union_audit_relationship_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_audit_relationship
    ADD CONSTRAINT cofk_union_audit_relationship_pkey PRIMARY KEY (audit_id);


--
-- Name: cofk_union_comment cofk_union_comment_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_comment
    ADD CONSTRAINT cofk_union_comment_pkey PRIMARY KEY (comment_id);


--
-- Name: cofk_union_favourite_language cofk_union_favourite_language_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_favourite_language
    ADD CONSTRAINT cofk_union_favourite_language_pkey PRIMARY KEY (language_code);


--
-- Name: cofk_union_image cofk_union_image_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_image
    ADD CONSTRAINT cofk_union_image_pkey PRIMARY KEY (image_id);


--
-- Name: cofk_union_institution cofk_union_institution_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_institution
    ADD CONSTRAINT cofk_union_institution_pkey PRIMARY KEY (institution_id);


--
-- Name: cofk_union_language_of_manifestation cofk_union_language_of_manifestation_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_language_of_manifestation
    ADD CONSTRAINT cofk_union_language_of_manifestation_pkey PRIMARY KEY (manifestation_id, language_code);


--
-- Name: cofk_union_language_of_work cofk_union_language_of_work_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_language_of_work
    ADD CONSTRAINT cofk_union_language_of_work_pkey PRIMARY KEY (work_id, language_code);


--
-- Name: cofk_union_location cofk_union_location_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_location
    ADD CONSTRAINT cofk_union_location_pkey PRIMARY KEY (location_id);


--
-- Name: cofk_union_manifestation cofk_union_manifestation_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_manifestation
    ADD CONSTRAINT cofk_union_manifestation_pkey PRIMARY KEY (manifestation_id);


--
-- Name: cofk_union_nationality cofk_union_nationality_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_nationality
    ADD CONSTRAINT cofk_union_nationality_pkey PRIMARY KEY (nationality_id);


--
-- Name: cofk_union_org_type cofk_union_org_type_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_org_type
    ADD CONSTRAINT cofk_union_org_type_pkey PRIMARY KEY (org_type_id);


--
-- Name: cofk_union_person cofk_union_person_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_person
    ADD CONSTRAINT cofk_union_person_pkey PRIMARY KEY (person_id);


--
-- Name: cofk_union_person_summary cofk_union_person_summary_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_person_summary
    ADD CONSTRAINT cofk_union_person_summary_pkey PRIMARY KEY (iperson_id);


--
-- Name: cofk_union_publication cofk_union_publication_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_publication
    ADD CONSTRAINT cofk_union_publication_pkey PRIMARY KEY (publication_id);


--
-- Name: cofk_union_queryable_work cofk_union_queryable_work_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_queryable_work
    ADD CONSTRAINT cofk_union_queryable_work_pkey PRIMARY KEY (iwork_id);


--
-- Name: cofk_union_relationship cofk_union_relationship_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_relationship
    ADD CONSTRAINT cofk_union_relationship_pkey PRIMARY KEY (relationship_id);


--
-- Name: cofk_union_relationship_type cofk_union_relationship_type_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_relationship_type
    ADD CONSTRAINT cofk_union_relationship_type_pkey PRIMARY KEY (relationship_code);


--
-- Name: cofk_union_resource cofk_union_resource_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_resource
    ADD CONSTRAINT cofk_union_resource_pkey PRIMARY KEY (resource_id);


--
-- Name: cofk_union_role_category cofk_union_role_category_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_role_category
    ADD CONSTRAINT cofk_union_role_category_pkey PRIMARY KEY (role_category_id);


--
-- Name: cofk_union_speed_entry_text cofk_union_speed_entry_text_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_speed_entry_text
    ADD CONSTRAINT cofk_union_speed_entry_text_pkey PRIMARY KEY (speed_entry_text_id);


--
-- Name: cofk_union_subject cofk_union_subject_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_subject
    ADD CONSTRAINT cofk_union_subject_pkey PRIMARY KEY (subject_id);


--
-- Name: cofk_union_work cofk_union_work_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_work
    ADD CONSTRAINT cofk_union_work_pkey PRIMARY KEY (work_id);


--
-- Name: cofk_help_options cofk_uniq_help_option_menu_item_button; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_help_options
    ADD CONSTRAINT cofk_uniq_help_option_menu_item_button UNIQUE (menu_item_id, button_name);


--
-- Name: cofk_lookup_catalogue cofk_uniq_lookup_catalogue_code; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_lookup_catalogue
    ADD CONSTRAINT cofk_uniq_lookup_catalogue_code UNIQUE (catalogue_code);


--
-- Name: cofk_lookup_catalogue cofk_uniq_lookup_catalogue_name; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_lookup_catalogue
    ADD CONSTRAINT cofk_uniq_lookup_catalogue_name UNIQUE (catalogue_name);


--
-- Name: cofk_roles cofk_uniq_role_code; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_roles
    ADD CONSTRAINT cofk_uniq_role_code UNIQUE (role_code);


--
-- Name: cofk_roles cofk_uniq_role_name; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_roles
    ADD CONSTRAINT cofk_uniq_role_name UNIQUE (role_name);


--
-- Name: cofk_sessions cofk_uniq_session_code; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_sessions
    ADD CONSTRAINT cofk_uniq_session_code UNIQUE (session_code);


--
-- Name: cofk_union_person cofk_uniq_union_iperson_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_person
    ADD CONSTRAINT cofk_uniq_union_iperson_id UNIQUE (iperson_id);


--
-- Name: cofk_union_work cofk_uniq_union_iwork_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_work
    ADD CONSTRAINT cofk_uniq_union_iwork_id UNIQUE (iwork_id);


--
-- Name: cofk_union_queryable_work cofk_uniq_union_queryable_work_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_queryable_work
    ADD CONSTRAINT cofk_uniq_union_queryable_work_id UNIQUE (work_id);


--
-- Name: cofk_user_roles cofk_user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_user_roles
    ADD CONSTRAINT cofk_user_roles_pkey PRIMARY KEY (username, role_id);


--
-- Name: cofk_user_saved_queries cofk_user_saved_queries_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_user_saved_queries
    ADD CONSTRAINT cofk_user_saved_queries_pkey PRIMARY KEY (query_id);


--
-- Name: cofk_user_saved_query_selection cofk_user_saved_query_selection_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_user_saved_query_selection
    ADD CONSTRAINT cofk_user_saved_query_selection_pkey PRIMARY KEY (selection_id);


--
-- Name: cofk_users cofk_users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_users
    ADD CONSTRAINT cofk_users_pkey PRIMARY KEY (username);


--
-- Name: iso_639_language_codes iso_639_language_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.iso_639_language_codes
    ADD CONSTRAINT iso_639_language_codes_pkey PRIMARY KEY (code_639_3);


--
-- Name: pro_activity pro_activity_pkey; Type: CONSTRAINT; Schema: public; Owner: cofktanya
--

ALTER TABLE ONLY public.pro_activity
    ADD CONSTRAINT pro_activity_pkey PRIMARY KEY (id);


--
-- Name: pro_activity_relation pro_activity_relation_pkey; Type: CONSTRAINT; Schema: public; Owner: cofktanya
--

ALTER TABLE ONLY public.pro_activity_relation
    ADD CONSTRAINT pro_activity_relation_pkey PRIMARY KEY (id);


--
-- Name: pro_assertion pro_assertion_pkey; Type: CONSTRAINT; Schema: public; Owner: cofktanya
--

ALTER TABLE ONLY public.pro_assertion
    ADD CONSTRAINT pro_assertion_pkey PRIMARY KEY (id);


--
-- Name: pro_location pro_location_pkey; Type: CONSTRAINT; Schema: public; Owner: cofktanya
--

ALTER TABLE ONLY public.pro_location
    ADD CONSTRAINT pro_location_pkey PRIMARY KEY (id);


--
-- Name: pro_primary_person pro_primary_person_pkey; Type: CONSTRAINT; Schema: public; Owner: cofktanya
--

ALTER TABLE ONLY public.pro_primary_person
    ADD CONSTRAINT pro_primary_person_pkey PRIMARY KEY (id);


--
-- Name: pro_relationship pro_relationship_pkey; Type: CONSTRAINT; Schema: public; Owner: cofktanya
--

ALTER TABLE ONLY public.pro_relationship
    ADD CONSTRAINT pro_relationship_pkey PRIMARY KEY (id);


--
-- Name: pro_role_in_activity pro_role_in_activity_pkey; Type: CONSTRAINT; Schema: public; Owner: cofktanya
--

ALTER TABLE ONLY public.pro_role_in_activity
    ADD CONSTRAINT pro_role_in_activity_pkey PRIMARY KEY (id);


--
-- Name: pro_textual_source pro_textual_source_pkey; Type: CONSTRAINT; Schema: public; Owner: cofktanya
--

ALTER TABLE ONLY public.pro_textual_source
    ADD CONSTRAINT pro_textual_source_pkey PRIMARY KEY (id);


--
-- Name: cofk_union_audit_literal_change_timestamp; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cofk_union_audit_literal_change_timestamp ON public.cofk_union_audit_literal USING btree (change_timestamp DESC NULLS LAST);


--
-- Name: cofk_union_audit_literal_change_user; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cofk_union_audit_literal_change_user ON public.cofk_union_audit_literal USING btree (change_user);


--
-- Name: cofk_union_relationship_left_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cofk_union_relationship_left_idx ON public.cofk_union_relationship USING btree (left_table_name, left_id_value, relationship_type);


--
-- Name: cofk_union_relationship_right_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cofk_union_relationship_right_idx ON public.cofk_union_relationship USING btree (right_table_name, right_id_value, relationship_type);


--
-- Name: cofk_union_comment cofk_union_comment_trg_audit_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_comment_trg_audit_del BEFORE DELETE ON public.cofk_union_comment FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_comment cofk_union_comment_trg_audit_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_comment_trg_audit_ins AFTER INSERT ON public.cofk_union_comment FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_comment cofk_union_comment_trg_audit_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_comment_trg_audit_upd AFTER UPDATE ON public.cofk_union_comment FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_comment cofk_union_comment_trg_cascade03_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_comment_trg_cascade03_del AFTER DELETE ON public.cofk_union_comment FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_comment cofk_union_comment_trg_cascade03_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_comment_trg_cascade03_ins AFTER INSERT ON public.cofk_union_comment FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_comment cofk_union_comment_trg_cascade03_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_comment_trg_cascade03_upd AFTER UPDATE ON public.cofk_union_comment FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_comment cofk_union_comment_trg_set_change_cols; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_comment_trg_set_change_cols BEFORE UPDATE ON public.cofk_union_comment FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_set_change_cols();


--
-- Name: cofk_union_image cofk_union_image_trg_audit_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_image_trg_audit_del BEFORE DELETE ON public.cofk_union_image FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_image cofk_union_image_trg_audit_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_image_trg_audit_ins AFTER INSERT ON public.cofk_union_image FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_image cofk_union_image_trg_audit_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_image_trg_audit_upd AFTER UPDATE ON public.cofk_union_image FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_image cofk_union_image_trg_cascade03_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_image_trg_cascade03_upd AFTER UPDATE ON public.cofk_union_image FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_image cofk_union_image_trg_set_change_cols; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_image_trg_set_change_cols BEFORE UPDATE ON public.cofk_union_image FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_set_change_cols();


--
-- Name: cofk_union_institution cofk_union_institution_trg_audit_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_institution_trg_audit_del BEFORE DELETE ON public.cofk_union_institution FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_institution cofk_union_institution_trg_audit_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_institution_trg_audit_ins AFTER INSERT ON public.cofk_union_institution FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_institution cofk_union_institution_trg_audit_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_institution_trg_audit_upd AFTER UPDATE ON public.cofk_union_institution FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_institution cofk_union_institution_trg_set_change_cols; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_institution_trg_set_change_cols BEFORE UPDATE ON public.cofk_union_institution FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_set_change_cols();


--
-- Name: cofk_union_language_of_manifestation cofk_union_language_of_manifestation_trg_cascade03_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_language_of_manifestation_trg_cascade03_del AFTER DELETE ON public.cofk_union_language_of_manifestation FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_refresh_language_of_manifestation();


--
-- Name: cofk_union_language_of_manifestation cofk_union_language_of_manifestation_trg_cascade03_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_language_of_manifestation_trg_cascade03_ins AFTER INSERT ON public.cofk_union_language_of_manifestation FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_refresh_language_of_manifestation();


--
-- Name: cofk_union_language_of_manifestation cofk_union_language_of_manifestation_trg_cascade03_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_language_of_manifestation_trg_cascade03_upd AFTER UPDATE ON public.cofk_union_language_of_manifestation FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_refresh_language_of_manifestation();


--
-- Name: cofk_union_language_of_work cofk_union_language_of_work_trg_cascade03_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_language_of_work_trg_cascade03_del AFTER DELETE ON public.cofk_union_language_of_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_refresh_language_of_work();


--
-- Name: cofk_union_language_of_work cofk_union_language_of_work_trg_cascade03_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_language_of_work_trg_cascade03_ins AFTER INSERT ON public.cofk_union_language_of_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_refresh_language_of_work();


--
-- Name: cofk_union_language_of_work cofk_union_language_of_work_trg_cascade03_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_language_of_work_trg_cascade03_upd AFTER UPDATE ON public.cofk_union_language_of_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_refresh_language_of_work();


--
-- Name: cofk_union_location cofk_union_location_trg_audit_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_location_trg_audit_del BEFORE DELETE ON public.cofk_union_location FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_location cofk_union_location_trg_audit_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_location_trg_audit_ins AFTER INSERT ON public.cofk_union_location FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_location cofk_union_location_trg_audit_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_location_trg_audit_upd AFTER UPDATE ON public.cofk_union_location FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_location cofk_union_location_trg_cascade03_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_location_trg_cascade03_del AFTER DELETE ON public.cofk_union_location FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_location cofk_union_location_trg_cascade03_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_location_trg_cascade03_ins AFTER INSERT ON public.cofk_union_location FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_location cofk_union_location_trg_cascade03_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_location_trg_cascade03_upd AFTER UPDATE ON public.cofk_union_location FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_location cofk_union_location_trg_set_change_cols; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_location_trg_set_change_cols BEFORE UPDATE ON public.cofk_union_location FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_set_change_cols();


--
-- Name: cofk_union_manifestation cofk_union_manifestation_trg_audit_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_manifestation_trg_audit_del BEFORE DELETE ON public.cofk_union_manifestation FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_manifestation cofk_union_manifestation_trg_audit_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_manifestation_trg_audit_ins AFTER INSERT ON public.cofk_union_manifestation FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_manifestation cofk_union_manifestation_trg_audit_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_manifestation_trg_audit_upd AFTER UPDATE ON public.cofk_union_manifestation FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_manifestation cofk_union_manifestation_trg_cascade03_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_manifestation_trg_cascade03_del AFTER DELETE ON public.cofk_union_manifestation FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_manifestation cofk_union_manifestation_trg_cascade03_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_manifestation_trg_cascade03_ins AFTER INSERT ON public.cofk_union_manifestation FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_manifestation cofk_union_manifestation_trg_cascade03_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_manifestation_trg_cascade03_upd AFTER UPDATE ON public.cofk_union_manifestation FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_manifestation cofk_union_manifestation_trg_set_change_cols; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_manifestation_trg_set_change_cols BEFORE UPDATE ON public.cofk_union_manifestation FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_set_change_cols();


--
-- Name: cofk_union_person cofk_union_person_trg_audit_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_person_trg_audit_del BEFORE DELETE ON public.cofk_union_person FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_person cofk_union_person_trg_audit_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_person_trg_audit_ins AFTER INSERT ON public.cofk_union_person FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_person cofk_union_person_trg_audit_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_person_trg_audit_upd AFTER UPDATE ON public.cofk_union_person FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_person cofk_union_person_trg_cascade03_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_person_trg_cascade03_del AFTER DELETE ON public.cofk_union_person FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_person cofk_union_person_trg_cascade03_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_person_trg_cascade03_ins AFTER INSERT ON public.cofk_union_person FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_person cofk_union_person_trg_cascade03_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_person_trg_cascade03_upd AFTER UPDATE ON public.cofk_union_person FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_person cofk_union_person_trg_set_change_cols; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_person_trg_set_change_cols BEFORE UPDATE ON public.cofk_union_person FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_set_change_cols();


--
-- Name: cofk_union_publication cofk_union_publication_trg_audit_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_publication_trg_audit_del BEFORE DELETE ON public.cofk_union_publication FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_publication cofk_union_publication_trg_audit_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_publication_trg_audit_ins AFTER INSERT ON public.cofk_union_publication FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_publication cofk_union_publication_trg_audit_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_publication_trg_audit_upd AFTER UPDATE ON public.cofk_union_publication FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_publication cofk_union_publication_trg_set_change_cols; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_publication_trg_set_change_cols BEFORE UPDATE ON public.cofk_union_publication FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_set_change_cols();


--
-- Name: cofk_union_queryable_work cofk_union_queryable_work_trg_set_change_cols; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_queryable_work_trg_set_change_cols BEFORE UPDATE ON public.cofk_union_queryable_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_set_change_cols();


--
-- Name: cofk_union_relationship cofk_union_relationship_trg_audit_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_relationship_trg_audit_del BEFORE DELETE ON public.cofk_union_relationship FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_relationship cofk_union_relationship_trg_audit_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_relationship_trg_audit_ins AFTER INSERT ON public.cofk_union_relationship FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_relationship cofk_union_relationship_trg_audit_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_relationship_trg_audit_upd AFTER UPDATE ON public.cofk_union_relationship FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_relationship cofk_union_relationship_trg_cascade02_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_relationship_trg_cascade02_del AFTER DELETE ON public.cofk_union_relationship FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade02_rel_changes();


--
-- Name: cofk_union_relationship cofk_union_relationship_trg_cascade02_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_relationship_trg_cascade02_ins AFTER INSERT ON public.cofk_union_relationship FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade02_rel_changes();


--
-- Name: cofk_union_relationship cofk_union_relationship_trg_cascade02_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_relationship_trg_cascade02_upd AFTER UPDATE ON public.cofk_union_relationship FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade02_rel_changes();


--
-- Name: cofk_union_relationship cofk_union_relationship_trg_set_change_cols; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_relationship_trg_set_change_cols BEFORE UPDATE ON public.cofk_union_relationship FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_set_change_cols();


--
-- Name: cofk_union_relationship_type cofk_union_relationship_type_trg_audit_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_relationship_type_trg_audit_del BEFORE DELETE ON public.cofk_union_relationship_type FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_relationship_type cofk_union_relationship_type_trg_audit_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_relationship_type_trg_audit_ins AFTER INSERT ON public.cofk_union_relationship_type FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_relationship_type cofk_union_relationship_type_trg_audit_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_relationship_type_trg_audit_upd AFTER UPDATE ON public.cofk_union_relationship_type FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_relationship_type cofk_union_relationship_type_trg_set_change_cols; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_relationship_type_trg_set_change_cols BEFORE UPDATE ON public.cofk_union_relationship_type FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_set_change_cols();


--
-- Name: cofk_union_resource cofk_union_resource_trg_audit_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_resource_trg_audit_del BEFORE DELETE ON public.cofk_union_resource FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_resource cofk_union_resource_trg_audit_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_resource_trg_audit_ins AFTER INSERT ON public.cofk_union_resource FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_resource cofk_union_resource_trg_audit_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_resource_trg_audit_upd AFTER UPDATE ON public.cofk_union_resource FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_resource cofk_union_resource_trg_cascade03_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_resource_trg_cascade03_del AFTER DELETE ON public.cofk_union_resource FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_resource cofk_union_resource_trg_cascade03_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_resource_trg_cascade03_ins AFTER INSERT ON public.cofk_union_resource FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_resource cofk_union_resource_trg_cascade03_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_resource_trg_cascade03_upd AFTER UPDATE ON public.cofk_union_resource FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_resource cofk_union_resource_trg_set_change_cols; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_resource_trg_set_change_cols BEFORE UPDATE ON public.cofk_union_resource FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_set_change_cols();


--
-- Name: cofk_union_work cofk_union_work_trg_audit_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_work_trg_audit_del BEFORE DELETE ON public.cofk_union_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_work cofk_union_work_trg_audit_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_work_trg_audit_ins AFTER INSERT ON public.cofk_union_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_work cofk_union_work_trg_audit_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_work_trg_audit_upd AFTER UPDATE ON public.cofk_union_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_audit_any();


--
-- Name: cofk_union_work cofk_union_work_trg_cascade01_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_work_trg_cascade01_del AFTER DELETE ON public.cofk_union_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade01_work_changes();


--
-- Name: cofk_union_work cofk_union_work_trg_cascade01_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_work_trg_cascade01_ins AFTER INSERT ON public.cofk_union_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade01_work_changes();


--
-- Name: cofk_union_work cofk_union_work_trg_cascade01_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_work_trg_cascade01_upd AFTER UPDATE ON public.cofk_union_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade01_work_changes();


--
-- Name: cofk_union_work cofk_union_work_trg_cascade03_del; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_work_trg_cascade03_del AFTER DELETE ON public.cofk_union_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_work cofk_union_work_trg_cascade03_ins; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_work_trg_cascade03_ins AFTER INSERT ON public.cofk_union_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_work cofk_union_work_trg_cascade03_upd; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_work_trg_cascade03_upd AFTER UPDATE ON public.cofk_union_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_union_cascade03_decodes();


--
-- Name: cofk_union_work cofk_union_work_trg_set_change_cols; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER cofk_union_work_trg_set_change_cols BEFORE UPDATE ON public.cofk_union_work FOR EACH ROW EXECUTE PROCEDURE public.dbf_cofk_set_change_cols();


--
-- Name: cofk_collect_addressee_of_work cofk_collect_addressee_of_work_fk_iperson_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_addressee_of_work
    ADD CONSTRAINT cofk_collect_addressee_of_work_fk_iperson_id FOREIGN KEY (upload_id, iperson_id) REFERENCES public.cofk_collect_person(upload_id, iperson_id);


--
-- Name: cofk_collect_addressee_of_work cofk_collect_addressee_of_work_fk_iwork_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_addressee_of_work
    ADD CONSTRAINT cofk_collect_addressee_of_work_fk_iwork_id FOREIGN KEY (upload_id, iwork_id) REFERENCES public.cofk_collect_work(upload_id, iwork_id);


--
-- Name: cofk_collect_addressee_of_work cofk_collect_addressee_of_work_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_addressee_of_work
    ADD CONSTRAINT cofk_collect_addressee_of_work_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_author_of_work cofk_collect_author_of_work_fk_iperson_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_author_of_work
    ADD CONSTRAINT cofk_collect_author_of_work_fk_iperson_id FOREIGN KEY (upload_id, iperson_id) REFERENCES public.cofk_collect_person(upload_id, iperson_id);


--
-- Name: cofk_collect_author_of_work cofk_collect_author_of_work_fk_iwork_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_author_of_work
    ADD CONSTRAINT cofk_collect_author_of_work_fk_iwork_id FOREIGN KEY (upload_id, iwork_id) REFERENCES public.cofk_collect_work(upload_id, iwork_id);


--
-- Name: cofk_collect_author_of_work cofk_collect_author_of_work_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_author_of_work
    ADD CONSTRAINT cofk_collect_author_of_work_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_destination_of_work cofk_collect_destination_of_work_fk_iwork_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_destination_of_work
    ADD CONSTRAINT cofk_collect_destination_of_work_fk_iwork_id FOREIGN KEY (upload_id, iwork_id) REFERENCES public.cofk_collect_work(upload_id, iwork_id);


--
-- Name: cofk_collect_work cofk_collect_destination_of_work_fk_location_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_work
    ADD CONSTRAINT cofk_collect_destination_of_work_fk_location_id FOREIGN KEY (upload_id, destination_id) REFERENCES public.cofk_collect_location(upload_id, location_id);


--
-- Name: cofk_collect_destination_of_work cofk_collect_destination_of_work_fk_location_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_destination_of_work
    ADD CONSTRAINT cofk_collect_destination_of_work_fk_location_id FOREIGN KEY (upload_id, location_id) REFERENCES public.cofk_collect_location(upload_id, location_id);


--
-- Name: cofk_collect_destination_of_work cofk_collect_destination_of_work_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_destination_of_work
    ADD CONSTRAINT cofk_collect_destination_of_work_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_upload cofk_collect_fk_upload_status; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_upload
    ADD CONSTRAINT cofk_collect_fk_upload_status FOREIGN KEY (upload_status) REFERENCES public.cofk_collect_status(status_id);


--
-- Name: cofk_collect_work cofk_collect_fk_work_status; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_work
    ADD CONSTRAINT cofk_collect_fk_work_status FOREIGN KEY (upload_status) REFERENCES public.cofk_collect_status(status_id);


--
-- Name: cofk_collect_image_of_manif cofk_collect_image_of_manif_fk_manifestation_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_image_of_manif
    ADD CONSTRAINT cofk_collect_image_of_manif_fk_manifestation_id FOREIGN KEY (upload_id, iwork_id, manifestation_id) REFERENCES public.cofk_collect_manifestation(upload_id, iwork_id, manifestation_id);


--
-- Name: cofk_collect_image_of_manif cofk_collect_image_of_manif_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_image_of_manif
    ADD CONSTRAINT cofk_collect_image_of_manif_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_institution cofk_collect_institution_fk_union_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_institution
    ADD CONSTRAINT cofk_collect_institution_fk_union_id FOREIGN KEY (union_institution_id) REFERENCES public.cofk_union_institution(institution_id) ON DELETE SET NULL;


--
-- Name: cofk_collect_institution cofk_collect_institution_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_institution
    ADD CONSTRAINT cofk_collect_institution_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_institution_resource cofk_collect_institution_resource_fk_institution_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_institution_resource
    ADD CONSTRAINT cofk_collect_institution_resource_fk_institution_id FOREIGN KEY (upload_id, institution_id) REFERENCES public.cofk_collect_institution(upload_id, institution_id);


--
-- Name: cofk_collect_institution_resource cofk_collect_institution_resource_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_institution_resource
    ADD CONSTRAINT cofk_collect_institution_resource_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_language_of_work cofk_collect_language_of_work_fk_iwork_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_language_of_work
    ADD CONSTRAINT cofk_collect_language_of_work_fk_iwork_id FOREIGN KEY (upload_id, iwork_id) REFERENCES public.cofk_collect_work(upload_id, iwork_id);


--
-- Name: cofk_collect_language_of_work cofk_collect_language_of_work_fk_language_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_language_of_work
    ADD CONSTRAINT cofk_collect_language_of_work_fk_language_id FOREIGN KEY (language_code) REFERENCES public.iso_639_language_codes(code_639_3);


--
-- Name: cofk_collect_language_of_work cofk_collect_language_of_work_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_language_of_work
    ADD CONSTRAINT cofk_collect_language_of_work_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_location cofk_collect_location_fk_union_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_location
    ADD CONSTRAINT cofk_collect_location_fk_union_id FOREIGN KEY (union_location_id) REFERENCES public.cofk_union_location(location_id) ON DELETE SET NULL;


--
-- Name: cofk_collect_location cofk_collect_location_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_location
    ADD CONSTRAINT cofk_collect_location_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_location_resource cofk_collect_location_resource_fk_location_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_location_resource
    ADD CONSTRAINT cofk_collect_location_resource_fk_location_id FOREIGN KEY (upload_id, location_id) REFERENCES public.cofk_collect_location(upload_id, location_id);


--
-- Name: cofk_collect_location_resource cofk_collect_location_resource_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_location_resource
    ADD CONSTRAINT cofk_collect_location_resource_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_manifestation cofk_collect_manifestation_fk_iwork_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_manifestation
    ADD CONSTRAINT cofk_collect_manifestation_fk_iwork_id FOREIGN KEY (upload_id, iwork_id) REFERENCES public.cofk_collect_work(upload_id, iwork_id);


--
-- Name: cofk_collect_manifestation cofk_collect_manifestation_fk_repos; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_manifestation
    ADD CONSTRAINT cofk_collect_manifestation_fk_repos FOREIGN KEY (upload_id, repository_id) REFERENCES public.cofk_collect_institution(upload_id, institution_id);


--
-- Name: cofk_collect_manifestation cofk_collect_manifestation_fk_union_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_manifestation
    ADD CONSTRAINT cofk_collect_manifestation_fk_union_id FOREIGN KEY (union_manifestation_id) REFERENCES public.cofk_union_manifestation(manifestation_id) ON DELETE SET NULL;


--
-- Name: cofk_collect_manifestation cofk_collect_manifestation_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_manifestation
    ADD CONSTRAINT cofk_collect_manifestation_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_occupation_of_person cofk_collect_occupation_of_person_fk_iperson_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_occupation_of_person
    ADD CONSTRAINT cofk_collect_occupation_of_person_fk_iperson_id FOREIGN KEY (upload_id, iperson_id) REFERENCES public.cofk_collect_person(upload_id, iperson_id);


--
-- Name: cofk_collect_occupation_of_person cofk_collect_occupation_of_person_fk_occupation_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_occupation_of_person
    ADD CONSTRAINT cofk_collect_occupation_of_person_fk_occupation_id FOREIGN KEY (occupation_id) REFERENCES public.cofk_union_role_category(role_category_id) ON DELETE CASCADE;


--
-- Name: cofk_collect_occupation_of_person cofk_collect_occupation_of_person_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_occupation_of_person
    ADD CONSTRAINT cofk_collect_occupation_of_person_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_origin_of_work cofk_collect_origin_of_work_fk_iwork_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_origin_of_work
    ADD CONSTRAINT cofk_collect_origin_of_work_fk_iwork_id FOREIGN KEY (upload_id, iwork_id) REFERENCES public.cofk_collect_work(upload_id, iwork_id);


--
-- Name: cofk_collect_work cofk_collect_origin_of_work_fk_location_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_work
    ADD CONSTRAINT cofk_collect_origin_of_work_fk_location_id FOREIGN KEY (upload_id, origin_id) REFERENCES public.cofk_collect_location(upload_id, location_id);


--
-- Name: cofk_collect_origin_of_work cofk_collect_origin_of_work_fk_location_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_origin_of_work
    ADD CONSTRAINT cofk_collect_origin_of_work_fk_location_id FOREIGN KEY (upload_id, location_id) REFERENCES public.cofk_collect_location(upload_id, location_id);


--
-- Name: cofk_collect_origin_of_work cofk_collect_origin_of_work_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_origin_of_work
    ADD CONSTRAINT cofk_collect_origin_of_work_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_person cofk_collect_person_fk_union_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_person
    ADD CONSTRAINT cofk_collect_person_fk_union_id FOREIGN KEY (person_id) REFERENCES public.cofk_union_person(person_id) ON DELETE SET NULL;


--
-- Name: cofk_collect_person cofk_collect_person_fk_union_iid; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_person
    ADD CONSTRAINT cofk_collect_person_fk_union_iid FOREIGN KEY (union_iperson_id) REFERENCES public.cofk_union_person(iperson_id) ON DELETE SET NULL;


--
-- Name: cofk_collect_person cofk_collect_person_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_person
    ADD CONSTRAINT cofk_collect_person_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_person_mentioned_in_work cofk_collect_person_mentioned_in_work_fk_iperson_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_person_mentioned_in_work
    ADD CONSTRAINT cofk_collect_person_mentioned_in_work_fk_iperson_id FOREIGN KEY (upload_id, iperson_id) REFERENCES public.cofk_collect_person(upload_id, iperson_id);


--
-- Name: cofk_collect_person_mentioned_in_work cofk_collect_person_mentioned_in_work_fk_iwork_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_person_mentioned_in_work
    ADD CONSTRAINT cofk_collect_person_mentioned_in_work_fk_iwork_id FOREIGN KEY (upload_id, iwork_id) REFERENCES public.cofk_collect_work(upload_id, iwork_id);


--
-- Name: cofk_collect_person_mentioned_in_work cofk_collect_person_mentioned_in_work_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_person_mentioned_in_work
    ADD CONSTRAINT cofk_collect_person_mentioned_in_work_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_person_resource cofk_collect_person_resource_fk_iperson_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_person_resource
    ADD CONSTRAINT cofk_collect_person_resource_fk_iperson_id FOREIGN KEY (upload_id, iperson_id) REFERENCES public.cofk_collect_person(upload_id, iperson_id);


--
-- Name: cofk_collect_person_resource cofk_collect_person_resource_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_person_resource
    ADD CONSTRAINT cofk_collect_person_resource_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_place_mentioned_in_work cofk_collect_place_mentioned_in_work_fk_iwork_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_place_mentioned_in_work
    ADD CONSTRAINT cofk_collect_place_mentioned_in_work_fk_iwork_id FOREIGN KEY (upload_id, iwork_id) REFERENCES public.cofk_collect_work(upload_id, iwork_id);


--
-- Name: cofk_collect_place_mentioned_in_work cofk_collect_place_mentioned_in_work_fk_location_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_place_mentioned_in_work
    ADD CONSTRAINT cofk_collect_place_mentioned_in_work_fk_location_id FOREIGN KEY (upload_id, location_id) REFERENCES public.cofk_collect_location(upload_id, location_id);


--
-- Name: cofk_collect_place_mentioned_in_work cofk_collect_place_mentioned_in_work_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_place_mentioned_in_work
    ADD CONSTRAINT cofk_collect_place_mentioned_in_work_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_subject_of_work cofk_collect_subject_of_work_fk_iwork_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_subject_of_work
    ADD CONSTRAINT cofk_collect_subject_of_work_fk_iwork_id FOREIGN KEY (upload_id, iwork_id) REFERENCES public.cofk_collect_work(upload_id, iwork_id);


--
-- Name: cofk_collect_subject_of_work cofk_collect_subject_of_work_fk_subject_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_subject_of_work
    ADD CONSTRAINT cofk_collect_subject_of_work_fk_subject_id FOREIGN KEY (subject_id) REFERENCES public.cofk_union_subject(subject_id) ON DELETE CASCADE;


--
-- Name: cofk_collect_subject_of_work cofk_collect_subject_of_work_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_subject_of_work
    ADD CONSTRAINT cofk_collect_subject_of_work_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_tool_session cofk_collect_tool_fk_sessions_username; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_tool_session
    ADD CONSTRAINT cofk_collect_tool_fk_sessions_username FOREIGN KEY (username) REFERENCES public.cofk_collect_tool_user(tool_user_email) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: cofk_collect_work cofk_collect_work_fk_union_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_work
    ADD CONSTRAINT cofk_collect_work_fk_union_id FOREIGN KEY (work_id) REFERENCES public.cofk_union_work(work_id) ON DELETE SET NULL;


--
-- Name: cofk_collect_work cofk_collect_work_fk_union_iid; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_work
    ADD CONSTRAINT cofk_collect_work_fk_union_iid FOREIGN KEY (union_iwork_id) REFERENCES public.cofk_union_work(iwork_id) ON DELETE SET NULL;


--
-- Name: cofk_collect_work cofk_collect_work_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_work
    ADD CONSTRAINT cofk_collect_work_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_collect_work_summary cofk_collect_work_fk_work_id_in_tool; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_work_summary
    ADD CONSTRAINT cofk_collect_work_fk_work_id_in_tool FOREIGN KEY (upload_id, work_id_in_tool) REFERENCES public.cofk_collect_work(upload_id, iwork_id);


--
-- Name: cofk_collect_work_resource cofk_collect_work_resource_fk_iwork_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_work_resource
    ADD CONSTRAINT cofk_collect_work_resource_fk_iwork_id FOREIGN KEY (upload_id, iwork_id) REFERENCES public.cofk_collect_work(upload_id, iwork_id);


--
-- Name: cofk_collect_work_resource cofk_collect_work_resource_fk_upload_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_collect_work_resource
    ADD CONSTRAINT cofk_collect_work_resource_fk_upload_id FOREIGN KEY (upload_id) REFERENCES public.cofk_collect_upload(upload_id);


--
-- Name: cofk_help_options cofk_fk_help_option_menu_item; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_help_options
    ADD CONSTRAINT cofk_fk_help_option_menu_item FOREIGN KEY (menu_item_id) REFERENCES public.cofk_menu(menu_item_id);


--
-- Name: cofk_help_options cofk_fk_help_option_page; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_help_options
    ADD CONSTRAINT cofk_fk_help_option_page FOREIGN KEY (help_page_id) REFERENCES public.cofk_help_pages(page_id);


--
-- Name: cofk_sessions cofk_fk_sessions_username; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_sessions
    ADD CONSTRAINT cofk_fk_sessions_username FOREIGN KEY (username) REFERENCES public.cofk_users(username);


--
-- Name: cofk_menu cofk_fk_tracking_menu_parent_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_menu
    ADD CONSTRAINT cofk_fk_tracking_menu_parent_id FOREIGN KEY (parent_id) REFERENCES public.cofk_menu(menu_item_id);


--
-- Name: cofk_union_relationship cofk_fk_union_relationship_type; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_relationship
    ADD CONSTRAINT cofk_fk_union_relationship_type FOREIGN KEY (relationship_type) REFERENCES public.cofk_union_relationship_type(relationship_code);


--
-- Name: cofk_user_roles cofk_fk_user_roles_role_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_user_roles
    ADD CONSTRAINT cofk_fk_user_roles_role_id FOREIGN KEY (role_id) REFERENCES public.cofk_roles(role_id);


--
-- Name: cofk_user_roles cofk_fk_user_roles_username; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_user_roles
    ADD CONSTRAINT cofk_fk_user_roles_username FOREIGN KEY (username) REFERENCES public.cofk_users(username);


--
-- Name: cofk_user_saved_queries cofk_fk_user_saved_queries_username; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_user_saved_queries
    ADD CONSTRAINT cofk_fk_user_saved_queries_username FOREIGN KEY (username) REFERENCES public.cofk_users(username);


--
-- Name: cofk_user_saved_query_selection cofk_fk_user_saved_query_selection_query_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_user_saved_query_selection
    ADD CONSTRAINT cofk_fk_user_saved_query_selection_query_id FOREIGN KEY (query_id) REFERENCES public.cofk_user_saved_queries(query_id);


--
-- Name: cofk_union_language_of_work cofk_union_fk_language_code; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_language_of_work
    ADD CONSTRAINT cofk_union_fk_language_code FOREIGN KEY (language_code) REFERENCES public.iso_639_language_codes(code_639_3) ON DELETE CASCADE;


--
-- Name: cofk_union_language_of_manifestation cofk_union_fk_language_code; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_language_of_manifestation
    ADD CONSTRAINT cofk_union_fk_language_code FOREIGN KEY (language_code) REFERENCES public.iso_639_language_codes(code_639_3) ON DELETE CASCADE;


--
-- Name: cofk_union_favourite_language cofk_union_fk_language_code; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_favourite_language
    ADD CONSTRAINT cofk_union_fk_language_code FOREIGN KEY (language_code) REFERENCES public.iso_639_language_codes(code_639_3) ON DELETE CASCADE;


--
-- Name: cofk_union_language_of_manifestation cofk_union_fk_manifestation_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_language_of_manifestation
    ADD CONSTRAINT cofk_union_fk_manifestation_id FOREIGN KEY (manifestation_id) REFERENCES public.cofk_union_manifestation(manifestation_id) ON DELETE CASCADE;


--
-- Name: cofk_union_person cofk_union_fk_org_type; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_person
    ADD CONSTRAINT cofk_union_fk_org_type FOREIGN KEY (organisation_type) REFERENCES public.cofk_union_org_type(org_type_id);


--
-- Name: cofk_union_person_summary cofk_union_fk_person_summary; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_person_summary
    ADD CONSTRAINT cofk_union_fk_person_summary FOREIGN KEY (iperson_id) REFERENCES public.cofk_union_person(iperson_id) ON DELETE CASCADE;


--
-- Name: cofk_union_language_of_work cofk_union_fk_work_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_language_of_work
    ADD CONSTRAINT cofk_union_fk_work_id FOREIGN KEY (work_id) REFERENCES public.cofk_union_work(work_id) ON DELETE CASCADE;


--
-- Name: cofk_union_queryable_work cofk_union_queryable_work_fk_work_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_queryable_work
    ADD CONSTRAINT cofk_union_queryable_work_fk_work_id FOREIGN KEY (work_id) REFERENCES public.cofk_union_work(work_id) ON DELETE CASCADE;


--
-- Name: cofk_union_work cofk_union_work_fk_original_catalogue; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_union_work
    ADD CONSTRAINT cofk_union_work_fk_original_catalogue FOREIGN KEY (original_catalogue) REFERENCES public.cofk_lookup_catalogue(catalogue_code) ON UPDATE CASCADE;


--
-- Name: cofk_reports cofkfk_reports_menu_item_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_reports
    ADD CONSTRAINT cofkfk_reports_menu_item_id FOREIGN KEY (menu_item_id) REFERENCES public.cofk_menu(menu_item_id);


--
-- Name: cofk_reports cofkfk_reports_report_group_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cofk_reports
    ADD CONSTRAINT cofkfk_reports_report_group_id FOREIGN KEY (report_group_id) REFERENCES public.cofk_report_groups(report_group_id);


--
-- Name: FUNCTION dbf_cofk_check_collect_tool_session(input_username text, input_session_code text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_check_collect_tool_session(input_username text, input_session_code text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_check_collect_tool_session(input_username text, input_session_code text) TO minimal;


--
-- Name: FUNCTION dbf_cofk_check_login_creds(input_username text, input_pw text, input_token text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_check_login_creds(input_username text, input_pw text, input_token text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_check_login_creds(input_username text, input_pw text, input_token text) TO minimal;


--
-- Name: FUNCTION dbf_cofk_check_session(input_username text, input_session_code text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_check_session(input_username text, input_session_code text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_check_session(input_username text, input_session_code text) TO minimal;


--
-- Name: FUNCTION dbf_cofk_collect_write_work_summary(upload_id_parm integer, iwork_id_parm integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_collect_write_work_summary(upload_id_parm integer, iwork_id_parm integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_collect_write_work_summary(upload_id_parm integer, iwork_id_parm integer) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_collect_write_work_summary(upload_id_parm integer, iwork_id_parm integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_collect_write_work_summary(upload_id_parm integer, iwork_id_parm integer) TO collector_role_cofk;


--
-- Name: FUNCTION dbf_cofk_create_sql_user(input_username character varying, grant_edit_role character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_create_sql_user(input_username character varying, grant_edit_role character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_create_sql_user(input_username character varying, grant_edit_role character varying) TO super_role_cofk;


--
-- Name: FUNCTION dbf_cofk_create_user(input_username character varying, input_password character varying, input_surname character varying, input_forename character varying, input_email text, grant_edit_role character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_create_user(input_username character varying, input_password character varying, input_surname character varying, input_forename character varying, input_email text, grant_edit_role character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_create_user(input_username character varying, input_password character varying, input_surname character varying, input_forename character varying, input_email text, grant_edit_role character varying) TO super_role_cofk;


--
-- Name: FUNCTION dbf_cofk_decode_username(input_username text, input_token text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_decode_username(input_username text, input_token text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_decode_username(input_username text, input_token text) TO minimal;


--
-- Name: FUNCTION dbf_cofk_delete_collect_tool_session(session_for_deletion text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_delete_collect_tool_session(session_for_deletion text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_delete_collect_tool_session(session_for_deletion text) TO minimal;


--
-- Name: FUNCTION dbf_cofk_delete_session(session_for_deletion text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_delete_session(session_for_deletion text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_delete_session(session_for_deletion text) TO minimal;


--
-- Name: FUNCTION dbf_cofk_delete_user(input_username character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_delete_user(input_username character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_delete_user(input_username character varying) TO super_role_cofk;


--
-- Name: FUNCTION dbf_cofk_get_column_label(column_name character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_get_column_label(column_name character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_get_column_label(column_name character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_get_column_label(column_name character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_get_table_label(table_name character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_get_table_label(table_name character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_get_table_label(table_name character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_get_table_label(table_name character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_save_collect_tool_session_data(input_username text, old_session text, new_session text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_save_collect_tool_session_data(input_username text, old_session text, new_session text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_save_collect_tool_session_data(input_username text, old_session text, new_session text) TO minimal;


--
-- Name: FUNCTION dbf_cofk_save_session_data(input_username text, old_session text, new_session text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_save_session_data(input_username text, old_session text, new_session text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_save_session_data(input_username text, old_session text, new_session text) TO minimal;


--
-- Name: FUNCTION dbf_cofk_select_user(input_username text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_select_user(input_username text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_select_user(input_username text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_select_user(input_username text) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_select_user(input_username text) TO collector_role_cofk;


--
-- Name: FUNCTION dbf_cofk_select_user_role_ids(input_username character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_select_user_role_ids(input_username character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_select_user_role_ids(input_username character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_select_user_role_ids(input_username character varying) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_select_user_role_ids(input_username character varying) TO collector_role_cofk;


--
-- Name: FUNCTION dbf_cofk_select_user_roles(input_username character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_select_user_roles(input_username character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_select_user_roles(input_username character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_select_user_roles(input_username character varying) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_select_user_roles(input_username character varying) TO collector_role_cofk;


--
-- Name: FUNCTION dbf_cofk_set_change_cols(); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_set_change_cols() FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_set_change_cols() TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_set_pw_by_super(md5_user_name character varying, token character varying, md5_pass character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_set_pw_by_super(md5_user_name character varying, token character varying, md5_pass character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_set_pw_by_super(md5_user_name character varying, token character varying, md5_pass character varying) TO super_role_cofk;


--
-- Name: FUNCTION dbf_cofk_set_user_login_status(input_username character varying, input_active integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_set_user_login_status(input_username character varying, input_active integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_set_user_login_status(input_username character varying, input_active integer) TO super_role_cofk;


--
-- Name: FUNCTION dbf_cofk_set_user_roles(input_username character varying, input_roles character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_set_user_roles(input_username character varying, input_roles character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_set_user_roles(input_username character varying, input_roles character varying) TO super_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_add_to_rels_decoded_string(input_table_name character varying, input_id_value character varying, input_start_date date, input_end_date date, html_output integer, expand_details integer, curr_item integer, input_string text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_add_to_rels_decoded_string(input_table_name character varying, input_id_value character varying, input_start_date date, input_end_date date, html_output integer, expand_details integer, curr_item integer, input_string text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_add_to_rels_decoded_string(input_table_name character varying, input_id_value character varying, input_start_date date, input_end_date date, html_output integer, expand_details integer, curr_item integer, input_string text) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_add_to_rels_decoded_string(input_table_name character varying, input_id_value character varying, input_start_date date, input_end_date date, html_output integer, expand_details integer, curr_item integer, input_string text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_add_to_rels_decoded_string(input_table_name character varying, input_id_value character varying, input_start_date date, input_end_date date, html_output integer, expand_details integer, curr_item integer, input_string text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_audit_any(); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_audit_any() FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_audit_any() TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_audit_literal_delete(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_old_column_value text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_audit_literal_delete(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_old_column_value text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_audit_literal_delete(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_old_column_value text) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_audit_literal_insert(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_new_column_value text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_audit_literal_insert(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_new_column_value text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_audit_literal_insert(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_new_column_value text) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_audit_literal_update(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_new_column_value text, input_old_column_value text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_audit_literal_update(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_new_column_value text, input_old_column_value text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_audit_literal_update(input_table_name character varying, input_key_value_text character varying, input_key_value_integer integer, input_key_decode text, input_column_name character varying, input_new_column_value text, input_old_column_value text) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_audit_relationship_delete(input_left_table_name character varying, input_left_id_value_old character varying, input_left_id_decode_old text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_old character varying, input_right_id_decode_old text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_audit_relationship_delete(input_left_table_name character varying, input_left_id_value_old character varying, input_left_id_decode_old text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_old character varying, input_right_id_decode_old text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_audit_relationship_delete(input_left_table_name character varying, input_left_id_value_old character varying, input_left_id_decode_old text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_old character varying, input_right_id_decode_old text) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_audit_relationship_insert(input_left_table_name character varying, input_left_id_value_new character varying, input_left_id_decode_new text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_new character varying, input_right_id_decode_new text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_audit_relationship_insert(input_left_table_name character varying, input_left_id_value_new character varying, input_left_id_decode_new text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_new character varying, input_right_id_decode_new text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_audit_relationship_insert(input_left_table_name character varying, input_left_id_value_new character varying, input_left_id_decode_new text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_new character varying, input_right_id_decode_new text) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_audit_relationship_update(input_left_table_name character varying, input_left_id_value_new character varying, input_left_id_decode_new text, input_left_id_value_old character varying, input_left_id_decode_old text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_new character varying, input_right_id_decode_new text, input_right_id_value_old character varying, input_right_id_decode_old text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_audit_relationship_update(input_left_table_name character varying, input_left_id_value_new character varying, input_left_id_decode_new text, input_left_id_value_old character varying, input_left_id_decode_old text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_new character varying, input_right_id_decode_new text, input_right_id_value_old character varying, input_right_id_decode_old text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_audit_relationship_update(input_left_table_name character varying, input_left_id_value_new character varying, input_left_id_decode_new text, input_left_id_value_old character varying, input_left_id_decode_old text, input_relationship_type character varying, input_relationship_decode_left_to_right character varying, input_relationship_decode_right_to_left character varying, input_right_table_name character varying, input_right_id_value_new character varying, input_right_id_decode_new text, input_right_id_value_old character varying, input_right_id_decode_old text) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_cascade01_work_changes(); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_cascade01_work_changes() FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_cascade01_work_changes() TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_cascade02_rel_changes(); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_cascade02_rel_changes() FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_cascade02_rel_changes() TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_cascade03_decodes(); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_cascade03_decodes() FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_cascade03_decodes() TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_convert_list_from_html(input_string text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_convert_list_from_html(input_string text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_convert_list_from_html(input_string text) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_convert_list_to_html(input_string text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_convert_list_to_html(input_string text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_convert_list_to_html(input_string text) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_convert_list_to_or_from_html(input_string text, html_output integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_convert_list_to_or_from_html(input_string text, html_output integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_convert_list_to_or_from_html(input_string text, html_output integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_convert_list_to_or_from_html(input_string text, html_output integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode(table_name character varying, key_value character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode(table_name character varying, key_value character varying, suppress_links integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying, suppress_links integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying, suppress_links integer) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying, suppress_links integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode(table_name character varying, key_value character varying, suppress_links integer, expand_details integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying, suppress_links integer, expand_details integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying, suppress_links integer, expand_details integer) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode(table_name character varying, key_value character varying, suppress_links integer, expand_details integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode_expanded(table_name character varying, key_value character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode_expanded(table_name character varying, key_value character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_expanded(table_name character varying, key_value character varying) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_expanded(table_name character varying, key_value character varying) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode_expanded(table_name character varying, key_value character varying, suppress_links integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode_expanded(table_name character varying, key_value character varying, suppress_links integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_expanded(table_name character varying, key_value character varying, suppress_links integer) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_expanded(table_name character varying, key_value character varying, suppress_links integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode_manifestation(key_value character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode_manifestation(key_value character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_manifestation(key_value character varying) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_manifestation(key_value character varying) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode_manifestation(key_value character varying, suppress_links integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode_manifestation(key_value character varying, suppress_links integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_manifestation(key_value character varying, suppress_links integer) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_manifestation(key_value character varying, suppress_links integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode_person(key_value character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode_person(key_value character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_person(key_value character varying) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_person(key_value character varying) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode_person(key_value character varying, suppress_links integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode_person(key_value character varying, suppress_links integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_person(key_value character varying, suppress_links integer) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_person(key_value character varying, suppress_links integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode_person(key_value character varying, suppress_links integer, expand_details integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode_person(key_value character varying, suppress_links integer, expand_details integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_person(key_value character varying, suppress_links integer, expand_details integer) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_person(key_value character varying, suppress_links integer, expand_details integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode_place_with_region(place_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode_place_with_region(place_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_place_with_region(place_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_place_with_region(place_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode_work(key_value character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode_work(key_value character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_work(key_value character varying) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_work(key_value character varying) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode_work(key_value character varying, suppress_links integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode_work(key_value character varying, suppress_links integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_work(key_value character varying, suppress_links integer) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_work(key_value character varying, suppress_links integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_decode_work_role_with_aliases(role_code character varying, input_work_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_decode_work_role_with_aliases(role_code character varying, input_work_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_work_role_with_aliases(role_code character varying, input_work_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_decode_work_role_with_aliases(role_code character varying, input_work_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_full_manifestation_details(input_work_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_full_manifestation_details(input_work_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_full_manifestation_details(input_work_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_full_manifestation_details(input_work_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_get_constant_value(constant_name character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_get_constant_value(constant_name character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_get_constant_value(constant_name character varying) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_get_constant_value(constant_name character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_get_constant_value(constant_name character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_get_language_of_manifestation(input_manifestation_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_get_language_of_manifestation(input_manifestation_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_get_language_of_manifestation(input_manifestation_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_get_language_of_manifestation(input_manifestation_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_get_language_of_work(input_work_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_get_language_of_work(input_work_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_get_language_of_work(input_work_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_get_language_of_work(input_work_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_get_work_desc(input_work_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_get_work_desc(input_work_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_get_work_desc(input_work_id character varying) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_get_work_desc(input_work_id character varying) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_link_to_edit_app(link_text character varying, query_string character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_link_to_edit_app(link_text character varying, query_string character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_link_to_edit_app(link_text character varying, query_string character varying) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_link_to_edit_app(link_text character varying, query_string character varying) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_addressees(work_id_parm text, html_output integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_addressees(work_id_parm text, html_output integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_addressees(work_id_parm text, html_output integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_addressees(work_id_parm text, html_output integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_addressees_for_display(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_addressees_for_display(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_addressees_for_display(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_addressees_for_display(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_addressees_searchable(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_addressees_searchable(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_addressees_searchable(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_addressees_searchable(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_comments_on_author(work_id_parm text, html_output integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_author(work_id_parm text, html_output integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_author(work_id_parm text, html_output integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_author(work_id_parm text, html_output integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_comments_on_author_for_display(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_author_for_display(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_author_for_display(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_author_for_display(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_comments_on_author_searchable(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_author_searchable(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_author_searchable(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_author_searchable(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_comments_on_work(work_id_parm text, html_output integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_work(work_id_parm text, html_output integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_work(work_id_parm text, html_output integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_work(work_id_parm text, html_output integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_comments_on_work_for_display(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_work_for_display(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_work_for_display(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_work_for_display(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_comments_on_work_searchable(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_work_searchable(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_work_searchable(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_comments_on_work_searchable(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_creators(work_id_parm text, html_output integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_creators(work_id_parm text, html_output integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_creators(work_id_parm text, html_output integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_creators(work_id_parm text, html_output integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_creators_for_display(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_creators_for_display(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_creators_for_display(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_creators_for_display(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_creators_searchable(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_creators_searchable(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_creators_searchable(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_creators_searchable(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_destinations(work_id_parm text, html_output integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_destinations(work_id_parm text, html_output integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_destinations(work_id_parm text, html_output integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_destinations(work_id_parm text, html_output integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_destinations_for_display(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_destinations_for_display(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_destinations_for_display(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_destinations_for_display(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_destinations_searchable(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_destinations_searchable(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_destinations_searchable(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_destinations_searchable(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_images_of_entity(input_tablename character varying, input_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_images_of_entity(input_tablename character varying, input_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_images_of_entity(input_tablename character varying, input_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_images_of_entity(input_tablename character varying, input_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_images_of_work(input_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_images_of_work(input_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_images_of_work(input_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_images_of_work(input_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_lefthand_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_lefthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_lefthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_lefthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_manifestation_images(input_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_manifestation_images(input_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_manifestation_images(input_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_manifestation_images(input_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_manifestations(work_id_parm text, html_output integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_manifestations(work_id_parm text, html_output integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_manifestations(work_id_parm text, html_output integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_manifestations(work_id_parm text, html_output integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_manifestations_for_display(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_manifestations_for_display(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_manifestations_for_display(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_manifestations_for_display(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_manifestations_searchable(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_manifestations_searchable(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_manifestations_searchable(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_manifestations_searchable(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_origins(work_id_parm text, html_output integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_origins(work_id_parm text, html_output integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_origins(work_id_parm text, html_output integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_origins(work_id_parm text, html_output integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_origins_for_display(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_origins_for_display(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_origins_for_display(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_origins_for_display(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_origins_searchable(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_origins_searchable(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_origins_searchable(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_origins_searchable(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_people_mentioned(work_id_parm text, html_output integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_people_mentioned(work_id_parm text, html_output integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_people_mentioned(work_id_parm text, html_output integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_people_mentioned(work_id_parm text, html_output integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_people_mentioned_for_display(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_people_mentioned_for_display(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_people_mentioned_for_display(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_people_mentioned_for_display(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_people_mentioned_searchable(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_people_mentioned_searchable(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_people_mentioned_searchable(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_people_mentioned_searchable(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_related_resources(input_table character varying, input_id character varying, full_details integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_related_resources(input_table character varying, input_id character varying, full_details integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_related_resources(input_table character varying, input_id character varying, full_details integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_related_resources(input_table character varying, input_id character varying, full_details integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_righthand_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rel_ids(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying, html_output integer, expand_details integer) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_righthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded_for_display(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_righthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO burgess;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_righthand_rels_decoded_searchable(required_table character varying, required_reltype character varying, known_table character varying, known_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_subjects_of_work(work_id_parm text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_subjects_of_work(work_id_parm text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_subjects_of_work(work_id_parm text) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_subjects_of_work(work_id_parm text) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_list_work_resources_brief(input_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_list_work_resources_brief(input_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_work_resources_brief(input_id character varying) TO editor_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_list_work_resources_brief(input_id character varying) TO viewer_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_person_summary(input_person_id integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id integer) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_person_summary(input_person_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_person_summary(input_person_id integer, include_works integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id integer, include_works integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id integer, include_works integer) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id integer, include_works integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_person_summary(input_person_id character varying, include_works integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying, include_works integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying, include_works integer) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying, include_works integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_person_summary(input_person_id character varying, include_works integer, expand_details integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying, include_works integer, expand_details integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying, include_works integer, expand_details integer) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary(input_person_id character varying, include_works integer, expand_details integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_person_summary_with_works(input_person_id integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_person_summary_with_works(input_person_id integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary_with_works(input_person_id integer) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary_with_works(input_person_id integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_person_summary_with_works(input_person_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_person_summary_with_works(input_person_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary_with_works(input_person_id character varying) TO viewer_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_union_person_summary_with_works(input_person_id character varying) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_primary_key(the_table_name character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_primary_key(the_table_name character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_primary_key(the_table_name character varying) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_refresh_language_of_manifestation(); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_refresh_language_of_manifestation() FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_refresh_language_of_manifestation() TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_refresh_language_of_work(); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_refresh_language_of_work() FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_refresh_language_of_work() TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_refresh_person_summary(input_id integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_refresh_person_summary(input_id integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_refresh_person_summary(input_id integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_refresh_person_summary(input_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_refresh_person_summary(input_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_refresh_person_summary(input_id character varying) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_refresh_person_summary(input_text_id character varying, input_integer_id integer); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_refresh_person_summary(input_text_id character varying, input_integer_id integer) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_refresh_person_summary(input_text_id character varying, input_integer_id integer) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_refresh_queryable_work(input_work_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_refresh_queryable_work(input_work_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_refresh_queryable_work(input_work_id character varying) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_union_refresh_work_desc(input_work_id character varying); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_union_refresh_work_desc(input_work_id character varying) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_union_refresh_work_desc(input_work_id character varying) TO editor_role_cofk;


--
-- Name: FUNCTION dbf_cofk_update_user_details(input_username character varying, input_surname character varying, input_forename character varying, input_email text); Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON FUNCTION public.dbf_cofk_update_user_details(input_username character varying, input_surname character varying, input_forename character varying, input_email text) FROM PUBLIC;
GRANT ALL ON FUNCTION public.dbf_cofk_update_user_details(input_username character varying, input_surname character varying, input_forename character varying, input_email text) TO super_role_cofk;
GRANT ALL ON FUNCTION public.dbf_cofk_update_user_details(input_username character varying, input_surname character varying, input_forename character varying, input_email text) TO editor_role_cofk;


--
-- Name: TABLE cofk_union_queryable_work; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_queryable_work TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_queryable_work TO viewer_role_cofk;


--
-- Name: TABLE cofk_cardindex_work_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_cardindex_work_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_cardindex_work_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_cardindex_compact_work_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_cardindex_compact_work_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_cardindex_compact_work_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_addressee_of_work; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_addressee_of_work TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_addressee_of_work TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_addressee_of_work TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_author_of_work; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_author_of_work TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_author_of_work TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_author_of_work TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_destination_of_work; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_destination_of_work TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_destination_of_work TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_destination_of_work TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_image_of_manif; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_image_of_manif TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_image_of_manif TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_image_of_manif TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_institution; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_institution TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_institution TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_institution TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_institution_resource; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_institution_resource TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_institution_resource TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_institution_resource TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_language_of_work; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_language_of_work TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_language_of_work TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_language_of_work TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_location; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_location TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_location TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_location TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_location_resource; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_location_resource TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_location_resource TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_location_resource TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_manifestation; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_manifestation TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_manifestation TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_manifestation TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_occupation_of_person; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_occupation_of_person TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_occupation_of_person TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_occupation_of_person TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_origin_of_work; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_origin_of_work TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_origin_of_work TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_origin_of_work TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_person; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_person TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_person TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_person TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_person_mentioned_in_work; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_person_mentioned_in_work TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_person_mentioned_in_work TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_person_mentioned_in_work TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_person_resource; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_person_resource TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_person_resource TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_person_resource TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_place_mentioned_in_work; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_place_mentioned_in_work TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_place_mentioned_in_work TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_place_mentioned_in_work TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_status; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_collect_status TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_status TO viewer_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_status TO collector_role_cofk;


--
-- Name: TABLE cofk_collect_subject_of_work; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_subject_of_work TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_subject_of_work TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_subject_of_work TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_collect_tool_user_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_collect_tool_user_id_seq TO editor_role_cofk;
GRANT ALL ON SEQUENCE public.cofk_collect_tool_user_id_seq TO collector_role_cofk;


--
-- Name: TABLE cofk_collect_tool_user; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_tool_user TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_tool_user TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_tool_user TO viewer_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_tool_user TO minimal;


--
-- Name: SEQUENCE cofk_collect_upload_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_collect_upload_id_seq TO editor_role_cofk;
GRANT ALL ON SEQUENCE public.cofk_collect_upload_id_seq TO collector_role_cofk;


--
-- Name: TABLE cofk_collect_upload; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_upload TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_upload TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_upload TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_work; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_work TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_work TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_work TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_work_resource; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_work_resource TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_work_resource TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_work_resource TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_work_summary; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_collect_work_summary TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_collect_work_summary TO collector_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_work_summary TO viewer_role_cofk;


--
-- Name: TABLE cofk_collect_work_summary_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_collect_work_summary_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_work_summary_view TO viewer_role_cofk;
GRANT SELECT ON TABLE public.cofk_collect_work_summary_view TO collector_role_cofk;


--
-- Name: SEQUENCE cofk_help_options_option_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_help_options_option_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_help_options; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_help_options TO super_role_cofk;
GRANT SELECT ON TABLE public.cofk_help_options TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_help_options TO viewer_role_cofk;
GRANT SELECT ON TABLE public.cofk_help_options TO webuser;


--
-- Name: SEQUENCE cofk_help_pages_page_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_help_pages_page_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_help_pages; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_help_pages TO super_role_cofk;
GRANT SELECT ON TABLE public.cofk_help_pages TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_help_pages TO viewer_role_cofk;
GRANT SELECT ON TABLE public.cofk_help_pages TO webuser;


--
-- Name: SEQUENCE cofk_lookup_catalogue_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_lookup_catalogue_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_lookup_catalogue; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_lookup_catalogue TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_lookup_catalogue TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_lookup_document_type_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_lookup_document_type_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_lookup_document_type; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_lookup_document_type TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_lookup_document_type TO viewer_role_cofk;
GRANT SELECT ON TABLE public.cofk_lookup_document_type TO collector_role_cofk;


--
-- Name: SEQUENCE cofk_menu_item_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_menu_item_id_seq TO editor_role_cofk;


--
-- Name: SEQUENCE cofk_menu_order_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_menu_order_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_menu; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_menu TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_menu TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_report_groups_report_group_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_report_groups_report_group_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_report_groups; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_report_groups TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_report_groups TO viewer_role_cofk;


--
-- Name: TABLE cofk_report_outputs; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_report_outputs TO super_role_cofk;
GRANT ALL ON TABLE public.cofk_report_outputs TO editor_role_cofk;
GRANT ALL ON TABLE public.cofk_report_outputs TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_reports_report_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_reports_report_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_reports; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_reports TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_reports TO viewer_role_cofk;


--
-- Name: TABLE cofk_roles; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_roles TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_roles TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_union_audit_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_audit_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_audit_literal; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT ON TABLE public.cofk_union_audit_literal TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_audit_literal TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_audit_relationship; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT ON TABLE public.cofk_union_audit_relationship TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_audit_relationship TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_audit_trail_column_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_audit_trail_column_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_audit_trail_column_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_audit_trail_table_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_audit_trail_table_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_audit_trail_table_view TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_union_person_iperson_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_person_iperson_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_person; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_person TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_person TO viewer_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_person TO collector_role_cofk;


--
-- Name: SEQUENCE cofk_union_work_iwork_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_work_iwork_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_work; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_work TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_work TO viewer_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_work TO collector_role_cofk;


--
-- Name: TABLE cofk_union_audit_trail_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_audit_trail_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_audit_trail_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_catalogue_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_catalogue_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_catalogue_view TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_union_comment_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_comment_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_comment; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_comment TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_comment TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_work_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_work_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_work_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_compact_work_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_compact_work_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_compact_work_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_favourite_language; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_favourite_language TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_favourite_language TO viewer_role_cofk;


--
-- Name: TABLE iso_639_language_codes; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.iso_639_language_codes TO PUBLIC;


--
-- Name: TABLE cofk_union_favourite_language_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_favourite_language_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_favourite_language_view TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_union_image_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_image_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_image; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_image TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_image TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_union_institution_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_institution_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_institution; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_institution TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_institution TO viewer_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_institution TO collector_role_cofk;


--
-- Name: TABLE cofk_union_institution_query_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_institution_query_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_institution_query_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_institution_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_institution_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_institution_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_language_of_manifestation; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_language_of_manifestation TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_language_of_manifestation TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_language_of_work; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_language_of_work TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_language_of_work TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_language_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_language_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_language_view TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_union_location_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_location_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_location; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_location TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_location TO viewer_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_location TO collector_role_cofk;


--
-- Name: SEQUENCE cofk_union_relationship_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_relationship_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_relationship; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_relationship TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_relationship TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_location_recd_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_location_recd_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_location_recd_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_location_sent_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_location_sent_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_location_sent_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_location_all_works_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_location_all_works_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_location_all_works_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_location_mentioned_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_location_mentioned_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_location_mentioned_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_location_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_location_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_location_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_manifestation; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_manifestation TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_manifestation TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_manifestation_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_manifestation_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_manifestation_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_manifestation_selection_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_manifestation_selection_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_manifestation_selection_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_modern_work_selection_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_modern_work_selection_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_modern_work_selection_view TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_union_nationality_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_nationality_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_nationality; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_nationality TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_nationality TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_union_org_type_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_org_type_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_org_type; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_org_type TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_org_type TO viewer_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_org_type TO collector_role_cofk;


--
-- Name: TABLE cofk_union_organisation_view_from_table; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_organisation_view_from_table TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_organisation_view_from_table TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_person_summary; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_person_summary TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_person_summary TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_person_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_person_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_person_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_organisation_view_from_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_organisation_view_from_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_organisation_view_from_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_person_recd_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_person_recd_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_person_recd_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_person_sent_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_person_sent_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_person_sent_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_person_all_works_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_person_all_works_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_person_all_works_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_person_mentioned_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_person_mentioned_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_person_mentioned_view TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_union_publication_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_publication_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_publication; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_publication TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_publication TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_relationship_type; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_relationship_type TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_relationship_type TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_union_resource_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_resource_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_resource; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_resource TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_resource TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_union_role_category_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_role_category_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_role_category; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_role_category TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_role_category TO viewer_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_role_category TO collector_role_cofk;


--
-- Name: SEQUENCE cofk_union_speed_entry_text_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_speed_entry_text_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_speed_entry_text; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_speed_entry_text TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_speed_entry_text TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_union_subject_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_union_subject_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_union_subject; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_union_subject TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_subject TO viewer_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_subject TO collector_role_cofk;


--
-- Name: TABLE cofk_union_work_image_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_work_image_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_work_image_view TO viewer_role_cofk;


--
-- Name: TABLE cofk_union_work_selection_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_union_work_selection_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_union_work_selection_view TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_user_saved_queries_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_user_saved_queries_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_user_saved_queries; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_user_saved_queries TO editor_role_cofk;


--
-- Name: SEQUENCE cofk_user_saved_query_selection_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_user_saved_query_selection_id_seq TO editor_role_cofk;


--
-- Name: TABLE cofk_user_saved_query_selection; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.cofk_user_saved_query_selection TO editor_role_cofk;


--
-- Name: TABLE cofk_users_and_roles_view; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT ON TABLE public.cofk_users_and_roles_view TO editor_role_cofk;
GRANT SELECT ON TABLE public.cofk_users_and_roles_view TO viewer_role_cofk;


--
-- Name: SEQUENCE cofk_users_username_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON SEQUENCE public.cofk_users_username_seq TO super_role_cofk;


--
-- Name: TABLE pro_activity; Type: ACL; Schema: public; Owner: cofktanya
--

GRANT ALL ON TABLE public.pro_activity TO cofkadmin;


--
-- Name: TABLE pro_activity_relation; Type: ACL; Schema: public; Owner: cofktanya
--

GRANT ALL ON TABLE public.pro_activity_relation TO cofkadmin;


--
-- Name: TABLE pro_assertion; Type: ACL; Schema: public; Owner: cofktanya
--

GRANT ALL ON TABLE public.pro_assertion TO cofkadmin;


--
-- Name: TABLE pro_location; Type: ACL; Schema: public; Owner: cofktanya
--

GRANT ALL ON TABLE public.pro_location TO cofkadmin;


--
-- Name: TABLE pro_primary_person; Type: ACL; Schema: public; Owner: cofktanya
--

GRANT ALL ON TABLE public.pro_primary_person TO cofkadmin;


--
-- Name: TABLE pro_relationship; Type: ACL; Schema: public; Owner: cofktanya
--

GRANT ALL ON TABLE public.pro_relationship TO cofkadmin;


--
-- Name: TABLE pro_role_in_activity; Type: ACL; Schema: public; Owner: cofktanya
--

GRANT ALL ON TABLE public.pro_role_in_activity TO cofkadmin;


--
-- Name: TABLE pro_textual_source; Type: ACL; Schema: public; Owner: cofktanya
--

GRANT ALL ON TABLE public.pro_textual_source TO cofkadmin;


--
-- PostgreSQL database dump complete
--

