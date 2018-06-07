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
    uuid uuid DEFAULT public.uuid_generate_v4(),
    address                      TEXT,
    latitude                     VARCHAR(20),
    longitude                    VARCHAR(20)
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
-- Data for Name: cofk_collect_addressee_of_work; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_addressee_of_work (upload_id, addressee_id, iperson_id, iwork_id, notes_on_addressee, _id) FROM stdin;
\.


--
-- Data for Name: cofk_collect_author_of_work; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_author_of_work (upload_id, author_id, iperson_id, iwork_id, notes_on_author, _id) FROM stdin;
\.


--
-- Data for Name: cofk_collect_destination_of_work; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_destination_of_work (upload_id, destination_id, location_id, iwork_id, notes_on_destination, _id) FROM stdin;
\.


--
-- Data for Name: cofk_collect_image_of_manif; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_image_of_manif (upload_id, manifestation_id, image_filename, _id, iwork_id) FROM stdin;
\.


--
-- Data for Name: cofk_collect_institution; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_institution (upload_id, institution_id, union_institution_id, institution_name, institution_city, institution_country, upload_name, _id, institution_synonyms, institution_city_synonyms, institution_country_synonyms) FROM stdin;
\.


--
-- Data for Name: cofk_collect_institution_resource; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_institution_resource (upload_id, resource_id, institution_id, resource_name, resource_details, resource_url) FROM stdin;
\.


--
-- Data for Name: cofk_collect_language_of_work; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_language_of_work (upload_id, language_of_work_id, iwork_id, language_code, _id) FROM stdin;
\.


--
-- Data for Name: cofk_collect_location; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_location (upload_id, location_id, union_location_id, location_name, element_1_eg_room, element_2_eg_building, element_3_eg_parish, element_4_eg_city, element_5_eg_county, element_6_eg_country, element_7_eg_empire, notes_on_place, editors_notes, upload_name, _id, location_synonyms, latitude, longitude) FROM stdin;
\.


--
-- Data for Name: cofk_collect_location_resource; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_location_resource (upload_id, resource_id, location_id, resource_name, resource_details, resource_url) FROM stdin;
\.


--
-- Data for Name: cofk_collect_manifestation; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_manifestation (upload_id, manifestation_id, iwork_id, union_manifestation_id, manifestation_type, repository_id, id_number_or_shelfmark, printed_edition_details, manifestation_notes, image_filenames, upload_name, _id) FROM stdin;
\.


--
-- Data for Name: cofk_collect_occupation_of_person; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_occupation_of_person (upload_id, occupation_of_person_id, iperson_id, occupation_id) FROM stdin;
\.


--
-- Data for Name: cofk_collect_origin_of_work; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_origin_of_work (upload_id, origin_id, location_id, iwork_id, notes_on_origin, _id) FROM stdin;
\.


--
-- Data for Name: cofk_collect_person; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_person (upload_id, iperson_id, union_iperson_id, person_id, primary_name, alternative_names, roles_or_titles, gender, is_organisation, organisation_type, date_of_birth_year, date_of_birth_month, date_of_birth_day, date_of_birth_is_range, date_of_birth2_year, date_of_birth2_month, date_of_birth2_day, date_of_birth_inferred, date_of_birth_uncertain, date_of_birth_approx, date_of_death_year, date_of_death_month, date_of_death_day, date_of_death_is_range, date_of_death2_year, date_of_death2_month, date_of_death2_day, date_of_death_inferred, date_of_death_uncertain, date_of_death_approx, flourished_year, flourished_month, flourished_day, flourished_is_range, flourished2_year, flourished2_month, flourished2_day, notes_on_person, editors_notes, upload_name, _id) FROM stdin;
\.


--
-- Data for Name: cofk_collect_person_mentioned_in_work; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_person_mentioned_in_work (upload_id, mention_id, iperson_id, iwork_id, notes_on_person_mentioned, _id) FROM stdin;
\.


--
-- Data for Name: cofk_collect_person_resource; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_person_resource (upload_id, resource_id, iperson_id, resource_name, resource_details, resource_url) FROM stdin;
\.


--
-- Data for Name: cofk_collect_place_mentioned_in_work; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_place_mentioned_in_work (upload_id, mention_id, location_id, iwork_id, notes_on_place_mentioned, _id) FROM stdin;
\.


--
-- Data for Name: cofk_collect_status; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_status (status_id, status_desc, editable) FROM stdin;
\.


--
-- Data for Name: cofk_collect_subject_of_work; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_subject_of_work (upload_id, subject_of_work_id, iwork_id, subject_id) FROM stdin;
\.


--
-- Data for Name: cofk_collect_tool_session; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_tool_session (session_id, session_timestamp, session_code, username) FROM stdin;
\.


--
-- Data for Name: cofk_collect_tool_user; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_tool_user (tool_user_id, tool_user_email, tool_user_surname, tool_user_forename, tool_user_pw) FROM stdin;
\.


--
-- Name: cofk_collect_tool_user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_collect_tool_user_id_seq', 1, false);


--
-- Data for Name: cofk_collect_upload; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_upload (upload_id, upload_username, upload_description, upload_status, upload_timestamp, total_works, works_accepted, works_rejected, uploader_email, _id, upload_name) FROM stdin;
\.


--
-- Name: cofk_collect_upload_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_collect_upload_id_seq', 1, false);


--
-- Data for Name: cofk_collect_work; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_work (upload_id, iwork_id, union_iwork_id, work_id, date_of_work_as_marked, original_calendar, date_of_work_std_year, date_of_work_std_month, date_of_work_std_day, date_of_work2_std_year, date_of_work2_std_month, date_of_work2_std_day, date_of_work_std_is_range, date_of_work_inferred, date_of_work_uncertain, date_of_work_approx, notes_on_date_of_work, authors_as_marked, authors_inferred, authors_uncertain, notes_on_authors, addressees_as_marked, addressees_inferred, addressees_uncertain, notes_on_addressees, destination_id, destination_as_marked, destination_inferred, destination_uncertain, origin_id, origin_as_marked, origin_inferred, origin_uncertain, abstract, keywords, language_of_work, incipit, excipit, accession_code, notes_on_letter, notes_on_people_mentioned, upload_status, editors_notes, _id, date_of_work2_approx, date_of_work2_inferred, date_of_work2_uncertain, mentioned_as_marked, mentioned_inferred, mentioned_uncertain, notes_on_destination, notes_on_origin, notes_on_place_mentioned, place_mentioned_as_marked, place_mentioned_inferred, place_mentioned_uncertain, upload_name, explicit) FROM stdin;
\.


--
-- Data for Name: cofk_collect_work_resource; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_work_resource (upload_id, resource_id, iwork_id, resource_name, resource_details, resource_url, _id) FROM stdin;
\.


--
-- Data for Name: cofk_collect_work_summary; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_collect_work_summary (upload_id, work_id_in_tool, source_of_data, notes_on_letter, date_of_work, date_of_work_as_marked, original_calendar, date_of_work_is_range, date_of_work_inferred, date_of_work_uncertain, date_of_work_approx, notes_on_date_of_work, authors, authors_as_marked, authors_inferred, authors_uncertain, notes_on_authors, addressees, addressees_as_marked, addressees_inferred, addressees_uncertain, notes_on_addressees, destination, destination_as_marked, destination_inferred, destination_uncertain, origin, origin_as_marked, origin_inferred, origin_uncertain, abstract, keywords, languages_of_work, subjects_of_work, incipit, excipit, people_mentioned, notes_on_people_mentioned, places_mentioned, manifestations, related_resources, editors_notes) FROM stdin;
\.


--
-- Data for Name: cofk_help_options; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_help_options (option_id, menu_item_id, button_name, help_page_id, order_in_manual, menu_depth) FROM stdin;
\.


--
-- Name: cofk_help_options_option_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_help_options_option_id_seq', 1, false);


--
-- Data for Name: cofk_help_pages; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_help_pages (page_id, page_title, custom_url, published_text, draft_text) FROM stdin;
\.


--
-- Name: cofk_help_pages_page_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_help_pages_page_id_seq', 1, false);


--
-- Data for Name: cofk_lookup_catalogue; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_lookup_catalogue (catalogue_id, catalogue_code, catalogue_name, is_in_union, publish_status) FROM stdin;
1		no catalogue specified	1	0
\.


--
-- Name: cofk_lookup_catalogue_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_lookup_catalogue_id_seq', 1, true);


--
-- Data for Name: cofk_lookup_document_type; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_lookup_document_type (document_type_id, document_type_code, document_type_desc) FROM stdin;
\.


--
-- Name: cofk_lookup_document_type_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_lookup_document_type_id_seq', 1, false);


--
-- Data for Name: cofk_menu; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_menu (menu_item_id, menu_item_name, menu_order, parent_id, has_children, class_name, method_name, user_restriction, hidden_parent, called_as_popup, collection) FROM stdin;
-1	Hidden options	11	-1	1	\N	\N		\N	0	
2	Work overview	21	-1	0	work	one_work_search_results		\N	0	
3	Search works (compact view)	31	\N	0	editable_work	db_search		\N	0	sample
4	Search works (compact view)	41	-1	0	editable_work	work_search_results		\N	0	sample
5	Search works (expanded view)	51	\N	0	editable_work	alternative_db_search		\N	0	sample
6	Search works (expanded view)	61	-1	0	editable_work	alternative_work_search_results		\N	0	sample
12	Search works (compact view)	121	\N	0	editable_work	db_search		\N	0	lister
13	Search works (compact view)	131	-1	0	editable_work	work_search_results		\N	0	lister
14	Search works (expanded view)	141	\N	0	editable_work	alternative_db_search		\N	0	lister
15	Search works (expanded view)	151	-1	0	editable_work	alternative_work_search_results		\N	0	lister
16	Add new work	161	\N	0	editable_work	add_work	cofkeditor	\N	0	union
17	Edit work	171	-1	0	editable_work	edit_work		\N	0	union
18	Edit work	181	-1	0	editable_work	save_work		\N	0	union
21	Upload images	211	-1	0	image	upload_files	cofkeditor	\N	0	
22	Upload images	221	-1	0	image	process_uploaded_files	cofkeditor	\N	0	
23	Search for, edit or merge people or organisations	231	\N	0	person	db_search	cofkeditor	\N	0	
24	Search for people or organisations	241	\N	0	person	db_search	cofkviewer	\N	0	
25	Add new person or organisation	251	\N	0	person	add_person	cofkeditor	\N	0	
26	View list of people and organisations	261	-1	0	person	db_search_results		\N	0	
27	Edit person or organisation	271	-1	0	person	one_person_search_results		\N	0	
28	Edit person or organisation	281	-1	0	person	save_person		\N	0	
29	Merge people or organisations	291	-1	0	person	start_merge		\N	0	
30	Merge people or organisations	301	-1	0	person	confirm_merge		\N	0	
31	Merge people or organisations	311	-1	0	person	save_merge		\N	0	
32	Delete person or organisation	321	-1	0	person	confirm_person_deletion	cofkeditor	\N	0	
33	Delete person or organisation	331	-1	0	person	delete_person	cofkeditor	\N	0	
35	Letters to or from correspondent	351	-1	0	editable_work	person_works_search_results		\N	0	
36	Works mentioning person or organisation	361	-1	0	editable_work	agent_mentioned_in_works_search_results		\N	0	
37	Search for, edit or merge places	371	\N	0	location	db_search	cofkeditor	\N	0	
38	Search for places	381	\N	0	location	db_search	cofkviewer	\N	0	
39	Add new place	391	\N	0	location	add_location	cofkeditor	\N	0	
40	View list of places	401	-1	0	location	db_search_results		\N	0	
41	Edit place	411	-1	0	location	one_location_search_results		\N	0	
42	Edit place	421	-1	0	location	save_location		\N	0	
43	Merge places	431	-1	0	location	start_merge		\N	0	
44	Merge places	441	-1	0	location	confirm_merge		\N	0	
45	Merge places	451	-1	0	location	save_merge		\N	0	
46	Delete place	461	-1	0	location	confirm_location_deletion	cofkeditor	\N	0	
47	Delete place	471	-1	0	location	delete_location	cofkeditor	\N	0	
48	Letters sent to or from place	481	-1	0	selden_work	location_works_search_results		\N	0	cardindex
49	Letters sent to or from place	491	-1	0	editable_work	location_works_search_results		\N	0	
50	Search works (compact view)	122	\N	0	editable_work	db_search		\N	0	comenius
51	Search works (compact view)	132	-1	0	editable_work	work_search_results		\N	0	comenius
52	Search works (expanded view)	142	\N	0	editable_work	alternative_db_search		\N	0	comenius
53	Search works (expanded view)	152	-1	0	editable_work	alternative_work_search_results		\N	0	comenius
54	Search works (compact view)	123	\N	0	editable_work	db_search		\N	0	comenius2
55	Search works (compact view)	133	-1	0	editable_work	work_search_results		\N	0	comenius2
56	Search works (expanded view)	143	\N	0	editable_work	alternative_db_search		\N	0	comenius2
57	Search works (expanded view)	153	-1	0	editable_work	alternative_work_search_results		\N	0	comenius2
58	Search works (compact view)	123	\N	0	editable_work	db_search		\N	0	hartlib
59	Search works (compact view)	133	-1	0	editable_work	work_search_results		\N	0	hartlib
60	Search works (expanded view)	143	\N	0	editable_work	alternative_db_search		\N	0	hartlib
61	Search works (expanded view)	153	-1	0	editable_work	alternative_work_search_results		\N	0	hartlib
62	Search works (compact view)	124	\N	0	editable_work	db_search		\N	0	lhwyd
63	Search works (compact view)	134	-1	0	editable_work	work_search_results		\N	0	lhwyd
64	Search works (expanded view)	144	\N	0	editable_work	alternative_db_search		\N	0	lhwyd
65	Search works (expanded view)	154	-1	0	editable_work	alternative_work_search_results		\N	0	lhwyd
66	Search works (compact view)	125	\N	0	editable_work	db_search		\N	0	aubrey
67	Search works (compact view)	135	-1	0	editable_work	work_search_results		\N	0	aubrey
68	Search works (expanded view)	145	\N	0	editable_work	alternative_db_search		\N	0	aubrey
69	Search works (expanded view)	155	-1	0	editable_work	alternative_work_search_results		\N	0	aubrey
70	Search works (compact view)	126	\N	0	editable_work	db_search		\N	0	union
71	Search works (compact view)	136	-1	0	editable_work	work_search_results		\N	0	union
72	Search works (expanded view)	146	\N	0	editable_work	alternative_db_search		\N	0	union
73	Search works (expanded view)	156	-1	0	editable_work	alternative_work_search_results		\N	0	union
74	Search for, edit or merge repositories	501	\N	0	repository	db_search	cofkeditor	\N	0	
75	Search for repositories	511	\N	0	repository	db_search	cofkviewer	\N	0	
76	Add new repository	521	\N	0	repository	add_repository	cofkeditor	\N	0	
77	View list of repositories	531	-1	0	repository	db_search_results		\N	0	
78	Edit repository	541	-1	0	repository	one_repository_search_results		\N	0	
79	Edit repository	551	-1	0	repository	save_repository		\N	0	
80	Merge repositories	561	-1	0	repository	start_merge		\N	0	
81	Merge repositories	571	-1	0	repository	confirm_merge		\N	0	
82	Merge repositories	581	-1	0	repository	save_merge		\N	0	
83	Search audit trail	591	\N	0	audit_trail	db_search	cofkeditor	\N	0	
84	Search audit trail	601	-1	0	audit_trail	audit_trail_search_results	cofkeditor	\N	0	
85	Display audit trail	611	-1	0	audit_trail	display_audit_trail	cofkeditor	\N	0	
86	Display audit trail	621	-1	0	audit_trail	one_work_search_results	cofkeditor	\N	0	
87	Search for and edit publications	631	\N	0	publication	db_search	cofkeditor	\N	0	
88	Search for publications	641	\N	0	publication	db_search	cofkviewer	\N	0	
89	Add new publication	651	\N	0	publication	add_publication	cofkeditor	\N	0	
90	View list of publications	661	-1	0	publication	db_search_results		\N	0	
91	Edit publication	671	-1	0	publication	edit_publication		\N	0	
92	Edit publication	681	-1	0	publication	save_publication		\N	0	
93	Select publication	691	-1	0	popup_publication	select_by_first_letter_of_name		\N	1	
94	Search for publication	701	-1	0	popup_publication	app_popup_search		\N	1	
95	View list of publications	711	-1	0	popup_publication	app_popup_search_results		\N	1	
96	Add publication	721	-1	0	popup_publication	app_popup_add_record	cofkeditor	\N	1	
97	Edit publication	731	-1	0	popup_publication	save_publication	cofkeditor	\N	1	
98	Select publication	741	-1	0	popup_publication_abbrev	select_by_first_letter_of_name		\N	1	
99	Search for publication	751	-1	0	popup_publication_abbrev	app_popup_search		\N	1	
100	View list of publications	761	-1	0	popup_publication_abbrev	app_popup_search_results		\N	1	
101	Add publication	771	-1	0	popup_publication_abbrev	app_popup_add_record	cofkeditor	\N	1	
102	Edit publication	781	-1	0	popup_publication_abbrev	save_publication	cofkeditor	\N	1	
103	Select languages for use in project	791	\N	0	language	db_search	cofkeditor	\N	0	
104	Select languages for use in project	801	-1	0	language	db_search_results	cofkeditor	\N	0	
105	Select languages for use in project	811	-1	0	language	save_language	cofkeditor	\N	0	
106	Add new options for fields	821	\N	1	\N	\N	cofkeditor	\N	0	
107	Add or edit subjects	831	106	0	subject	edit_lookup_table1	cofkeditor	\N	0	
108	Add or edit subjects	841	-1	0	subject	edit_lookup_table2	cofkeditor	106	0	
109	Add or edit 'speed entry' titles for resources	851	106	0	speed_entry_text	edit_lookup_table1	cofkeditor	\N	0	
110	Add or edit 'speed entry' titles for resources	861	-1	0	speed_entry_text	edit_lookup_table2	cofkeditor	106	0	
111	Add or edit professional categories	871	106	0	role_category	edit_lookup_table1	cofkeditor	\N	0	
112	Add or edit professional categories	881	-1	0	role_category	edit_lookup_table2	cofkeditor	106	0	
113	Add or edit organisation types	891	106	0	org_type	edit_lookup_table1	cofkeditor	\N	0	
114	Add or edit organisation types	901	-1	0	org_type	edit_lookup_table2	cofkeditor	106	0	
115	Add or edit catalogues	911	106	0	catalogue	edit_lookup_table1	cofkeditor	\N	0	
116	Add or edit catalogues	921	-1	0	catalogue	edit_lookup_table2	cofkeditor	106	0	
117	Supervisor-only options	931	\N	1	\N	\N	super	\N	0	
118	Your own reports/saved queries	941	\N	0	report	saved_query_list		\N	0	
119	Edit title of saved query	951	-1	0	report	edit_saved_query		\N	0	
120	Edit title of saved query	961	-1	0	report	edit_saved_query2		\N	0	
121	Delete saved query	971	-1	0	report	delete_saved_query		\N	0	
122	Delete saved query	981	-1	0	report	delete_saved_query2		\N	0	
123	Offline data collection tool	991	\N	1	\N	\N		\N	0	
124	Options for contributors	1001	123	0	tool_menu	contributor_menu		\N	0	
125	Options for reviewers	1011	123	0	tool_menu	reviewer_menu	super	\N	0	
126	View list of contributors	1021	125	0	contributor	db_search	super	\N	0	
127	View list of contributors	1031	-1	0	contributor	db_search_results	super	125	0	
128	Export contribution data to spreadsheet	1041	124	0	upload	export_upload_to_csv		\N	0	
129	Search and browse contributed works	1051	125	0	contributed_work	db_search	super	\N	0	
130	Search and browse contributed works	1061	125	0	contributed_work	db_search_results	super	\N	0	
131	Review contributions from EMLO-collect	1071	125	0	upload	upload_list	super	\N	0	
132	Review contribution from EMLO-collect	1081	-1	0	upload	upload_details	super	125	0	
133	Accept entire contribution	1091	-1	0	review	accept_all_works	super	125	0	
134	Reject entire contribution	1101	-1	0	review	reject_all_works	super	125	0	
135	Accept one work	1111	-1	0	review	accept_one_work	super	125	0	
136	Reject one work	1121	-1	0	review	reject_one_work	super	125	0	
137	Database users	1131	117	0	user	browse_users	super	\N	0	
138	Edit user	1141	-1	0	user	edit_user1_other	super	117	0	
139	Edit user	1151	-1	0	user	edit_user2_other	super	117	0	
140	Edit user	1161	-1	0	user	save_user_password_other	super	117	0	
141	Edit your own details	1171	\N	0	user	edit_user1_self		\N	0	
142	Edit your own details	1181	-1	0	user	edit_user2_self		\N	0	
143	Edit your own details	1191	-1	0	user	save_user_password_own		\N	0	
144	Delete user	1201	-1	0	user	delete_user2	super	117	0	
145	Person or organisation: More Details	1211	-1	0	person	moreinfo		\N	1	
146	Select person or organisation	1221	-1	0	popup_person	select_by_first_letter_of_name		\N	1	
147	Search for person or organisation	1231	-1	0	popup_person	app_popup_search		\N	1	
148	View list of people or organisations	1241	-1	0	popup_person	app_popup_search_results		\N	1	
149	Edit person or organisation	1251	-1	0	popup_person	one_person_search_results		\N	1	
150	Edit person or organisation	1261	-1	0	popup_person	save_person		\N	1	
151	Add person or organisation	1271	-1	0	popup_person	app_popup_add_record	cofkeditor	\N	1	
152	Merge people or organisations	1281	-1	0	popup_person	start_merge		\N	1	
153	Merge people or organisations	1291	-1	0	popup_person	confirm_merge		\N	1	
154	Merge people or organisations	1301	-1	0	popup_person	save_merge		\N	1	
155	Select organisation	1311	-1	0	popup_organisation	select_by_first_letter_of_name		\N	1	
156	Search for organisation	1321	-1	0	popup_organisation	app_popup_search		\N	1	
157	View list of people or organisations	1331	-1	0	popup_organisation	app_popup_search_results		\N	1	
158	Edit organisation	1341	-1	0	popup_organisation	one_person_search_results		\N	1	
159	Edit organisation	1351	-1	0	popup_organisation	save_person		\N	1	
160	Add organisation	1361	-1	0	popup_organisation	app_popup_add_record	cofkeditor	\N	1	
161	Merge organisations	1371	-1	0	popup_organisation	start_merge		\N	1	
162	Merge organisations	1381	-1	0	popup_organisation	confirm_merge		\N	1	
163	Merge organisations	1391	-1	0	popup_organisation	save_merge		\N	1	
164	Select from existing people or organisations	1401	-1	0	popup_person	existing_people_for_work_search_results		\N	1	
165	Select place	1411	-1	0	popup_location	select_by_first_letter_of_name		\N	1	
166	Search for place	1421	-1	0	popup_location	app_popup_search		\N	1	
167	View list of places	1431	-1	0	popup_location	app_popup_search_results		\N	1	
168	Edit place	1441	-1	0	popup_location	one_location_search_results		\N	1	
169	Edit place	1451	-1	0	popup_location	save_location		\N	1	
170	Add place	1461	-1	0	popup_location	app_popup_add_record	cofkeditor	\N	1	
171	Merge places	1471	-1	0	popup_location	start_merge		\N	1	
172	Merge places	1481	-1	0	popup_location	confirm_merge		\N	1	
173	Merge places	1491	-1	0	popup_location	save_merge		\N	1	
174	Search for manifestation	1501	-1	0	popup_manifestation	app_popup_search		\N	1	
175	View list of manifestations	1511	-1	0	popup_manifestation	app_popup_search_results		\N	1	
176	Search for work	1521	-1	0	popup_work	app_popup_search		\N	1	
177	View list of works	1531	-1	0	popup_work	app_popup_search_results		\N	1	
7	Search works (compact view)	71	\N	0	selden_work	db_search		\N	0	cardindex
8	Search works (compact view)	81	-1	0	selden_work	work_search_results		\N	0	cardindex
9	Search works (expanded view)	91	\N	0	selden_work	alternative_db_search		\N	0	cardindex
10	Search works (expanded view)	101	-1	0	selden_work	alternative_work_search_results		\N	0	cardindex
11	Display enlarged images	111	-1	0	selden_work	display_images		\N	0	cardindex
19	Edit work	191	-1	0	selden_work	edit_work		\N	0	cardindex
20	Edit work	201	-1	0	selden_work	save_work		\N	0	cardindex
34	Letters to or from correspondent	341	-1	0	selden_work	person_works_search_results		\N	0	cardindex
178	Contributor style upload	1541	125	0	upload	file_upload_excel_form	super	\N	0	
179	Upload Excel	1551	-1	0	upload	file_upload_excel_form_response		\N	0	
\.


--
-- Name: cofk_menu_item_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_menu_item_id_seq', 1, false);


--
-- Name: cofk_menu_order_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_menu_order_seq', 1, false);


--
-- Data for Name: cofk_report_groups; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_report_groups (report_group_id, report_group_title, report_group_order, on_main_reports_menu, report_group_code) FROM stdin;
\.


--
-- Name: cofk_report_groups_report_group_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_report_groups_report_group_id_seq', 1, false);


--
-- Data for Name: cofk_report_outputs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_report_outputs (output_id, line_number, line_text) FROM stdin;
\.


--
-- Data for Name: cofk_reports; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_reports (report_id, report_title, class_name, method_name, report_group_id, menu_item_id, has_csv_option, is_dummy_option, report_code, parm_list, parm_titles, prompt_for_parms, default_parm_values, parm_methods, report_help) FROM stdin;
\.


--
-- Name: cofk_reports_report_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_reports_report_id_seq', 1, false);


--
-- Data for Name: cofk_roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_roles (role_id, role_code, role_name) FROM stdin;
2	cofkviewer	Read-only access
-1	super	*Supervisor*
1	cofkeditor	Can edit Union and Bodleian card index catalogues
3	reviewer	Informed of new uploads from data collection tool
\.


--
-- Name: cofk_roles_role_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_roles_role_id_seq', 1, false);


--
-- Data for Name: cofk_sessions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_sessions (session_id, session_timestamp, session_code, username) FROM stdin;
1	2018-05-30 12:23:29.201168	0be2658210c049a6e7545a15802c0cb1	cofka
\.


--
-- Name: cofk_sessions_session_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_sessions_session_id_seq', 1, true);


--
-- Name: cofk_union_audit_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_audit_id_seq', 216, true);


--
-- Data for Name: cofk_union_audit_literal; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_audit_literal (audit_id, change_timestamp, change_user, change_type, table_name, key_value_text, key_value_integer, key_decode, column_name, new_column_value, old_column_value) FROM stdin;
1	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_involved_in	\N	Was involved in	desc_left_to_right	Was involved in	\N
2	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_involved_in	\N	Was involved in	desc_right_to_left	Affected	\N
3	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	created	\N	Created	desc_left_to_right	Created	\N
4	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	created	\N	Created	desc_right_to_left	Was created by	\N
5	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	handwrote	\N	handwrote	desc_left_to_right	handwrote	\N
6	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	handwrote	\N	handwrote	desc_right_to_left	in hand of	\N
7	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	partly_handwrote	\N	partly handwrote	desc_left_to_right	partly handwrote	\N
8	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	partly_handwrote	\N	partly handwrote	desc_right_to_left	partly in hand of	\N
9	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_rightsholder_of	\N	Is rightsholder of	desc_left_to_right	Is rightsholder of	\N
10	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_rightsholder_of	\N	Is rightsholder of	desc_right_to_left	Is copyright of	\N
11	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	sent	\N	Sent	desc_left_to_right	Sent	\N
12	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	sent	\N	Sent	desc_right_to_left	Was sent by	\N
13	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	signed	\N	Was signatory of	desc_left_to_right	Was signatory of	\N
14	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	signed	\N	Was signatory of	desc_right_to_left	Was signed by	\N
15	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	member_of	\N	Member of	desc_left_to_right	Member of	\N
16	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	member_of	\N	Member of	desc_right_to_left	Includes	\N
17	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_addressed_to	\N	Was addressed to	desc_left_to_right	Was addressed to	\N
18	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_addressed_to	\N	Was addressed to	desc_right_to_left	Was addressee of	\N
19	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_sent_from	\N	Was sent from	desc_left_to_right	Was sent from	\N
20	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_sent_from	\N	Was sent from	desc_right_to_left	Was source of	\N
21	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_sent_to	\N	Was sent to	desc_left_to_right	Was sent to	\N
22	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_sent_to	\N	Was sent to	desc_right_to_left	Was destination of	\N
23	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	enclosed_in	\N	Was enclosed in	desc_left_to_right	Was enclosed in	\N
24	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	enclosed_in	\N	Was enclosed in	desc_right_to_left	Had enclosure	\N
25	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	paper_reused_for	\N	Paper was re-used for later work	desc_left_to_right	Paper was re-used for later work	\N
26	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	paper_reused_for	\N	Paper was re-used for later work	desc_right_to_left	Re-used paper from earlier work	\N
27	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	copied	\N	Copied	desc_left_to_right	Copied	\N
28	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	copied	\N	Copied	desc_right_to_left	Was copied by	\N
29	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	transcribed	\N	Transcribed	desc_left_to_right	Transcribed	\N
30	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	transcribed	\N	Transcribed	desc_right_to_left	Was transcribed by	\N
31	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	edited	\N	Edited	desc_left_to_right	Edited	\N
32	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	edited	\N	Edited	desc_right_to_left	Was edited by	\N
33	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to	\N	Refers to	desc_left_to_right	Refers to	\N
34	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to	\N	Refers to	desc_right_to_left	Has note	\N
35	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_manifestation_of	\N	Is manifestation of	desc_left_to_right	Is manifestation of	\N
36	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_manifestation_of	\N	Is manifestation of	desc_right_to_left	Has manifestation	\N
37	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_reply_to	\N	Is reply to	desc_left_to_right	Is reply to	\N
38	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_reply_to	\N	Is reply to	desc_right_to_left	Is answered by	\N
39	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_related_to	\N	Is related to	desc_left_to_right	Is related to	\N
40	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_related_to	\N	Is related to	desc_right_to_left	Is related to	\N
41	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_translation_of	\N	Is translation of	desc_left_to_right	Is translation of	\N
42	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_translation_of	\N	Is translation of	desc_right_to_left	Is translated by	\N
43	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_transcription_of	\N	Is transcription of	desc_left_to_right	Is transcription of	\N
44	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_transcription_of	\N	Is transcription of	desc_right_to_left	Is transcribed in	\N
45	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_finding_aid_for	\N	Is finding aid for	desc_left_to_right	Is finding aid for	\N
46	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_finding_aid_for	\N	Is finding aid for	desc_right_to_left	Has finding aid	\N
47	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	quotes_from	\N	Is quote from	desc_left_to_right	Is quote from	\N
48	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	quotes_from	\N	Is quote from	desc_right_to_left	Is quoted in	\N
49	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	image_of	\N	Is image of	desc_left_to_right	Is image of	\N
50	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	image_of	\N	Is image of	desc_right_to_left	Has image	\N
51	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	mentions	\N	Mentions	desc_left_to_right	Mentions	\N
52	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	mentions	\N	Mentions	desc_right_to_left	Is mentioned in	\N
53	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_in_or_near	\N	Is in the area of	desc_left_to_right	Is in the area of	\N
54	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_in_or_near	\N	Is in the area of	desc_right_to_left	Includes	\N
55	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	stored_in	\N	Is in repository	desc_left_to_right	Is in repository	\N
56	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	stored_in	\N	Is in repository	desc_right_to_left	Has contents	\N
57	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	has_flag	\N	Has problem flagged	desc_left_to_right	Has problem flagged	\N
58	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	has_flag	\N	Has problem flagged	desc_right_to_left	Flags problem in	\N
59	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	formerly_owned	\N	was former owner of	desc_left_to_right	was former owner of	\N
60	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	formerly_owned	\N	was former owner of	desc_right_to_left	was formerly owned by	\N
61	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_author	\N	refers to author of work	desc_left_to_right	refers to author of work	\N
62	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_author	\N	refers to author of work	desc_right_to_left	has comment on author	\N
63	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_addressee	\N	refers to addressee of work	desc_left_to_right	refers to addressee of work	\N
64	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_addressee	\N	refers to addressee of work	desc_right_to_left	has comment on addressee	\N
65	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	intended_for	\N	was intended for	desc_left_to_right	was intended for	\N
66	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	intended_for	\N	was intended for	desc_right_to_left	was supposed to receive	\N
67	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_date	\N	refers to date of	desc_left_to_right	refers to date of	\N
68	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_date	\N	refers to date of	desc_right_to_left	has note on date	\N
69	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	forms_part_of_catg	\N	forms part of catalogue	desc_left_to_right	forms part of catalogue	\N
70	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	forms_part_of_catg	\N	forms part of catalogue	desc_right_to_left	has catalogue entry	\N
71	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	mentions_work	\N	mentions	desc_left_to_right	mentions	\N
72	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	mentions_work	\N	mentions	desc_right_to_left	is mentioned by	\N
73	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	mentions_place	\N	mentions	desc_left_to_right	mentions	\N
74	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	mentions_place	\N	mentions	desc_right_to_left	is mentioned by	\N
75	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_people_mentioned_in_work	\N	refers to people mentioned in	desc_left_to_right	refers to people mentioned in	\N
76	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_people_mentioned_in_work	\N	refers to people mentioned in	desc_right_to_left	has comment on people mentioned	\N
77	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_in_location	\N	was based in or visited	desc_left_to_right	was based in or visited	\N
78	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_in_location	\N	was based in or visited	desc_right_to_left	had inhabitant or visitor	\N
79	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	parent_of	\N	was the parent of	desc_left_to_right	was the parent of	\N
80	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	parent_of	\N	was the parent of	desc_right_to_left	was the child of	\N
81	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	spouse_of	\N	was married to	desc_left_to_right	was married to	\N
82	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	spouse_of	\N	was married to	desc_right_to_left	was married to	\N
83	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	sibling_of	\N	was a sibling of	desc_left_to_right	was a sibling of	\N
84	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	sibling_of	\N	was a sibling of	desc_right_to_left	was a sibling of	\N
85	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	relative_of	\N	was a relative of	desc_left_to_right	was a relative of	\N
86	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	relative_of	\N	was a relative of	desc_right_to_left	was a relative of	\N
87	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	friend_of	\N	was a friend of	desc_left_to_right	was a friend of	\N
88	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	friend_of	\N	was a friend of	desc_right_to_left	was a friend of	\N
89	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	colleague_of	\N	was a colleague of	desc_left_to_right	was a colleague of	\N
90	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	colleague_of	\N	was a colleague of	desc_right_to_left	was a colleague of	\N
91	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	collaborated_with	\N	collaborated with	desc_left_to_right	collaborated with	\N
92	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	collaborated_with	\N	collaborated with	desc_right_to_left	collaborated with	\N
93	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_a_business_associate_of	\N	was a business associate of	desc_left_to_right	was a business associate of	\N
94	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_a_business_associate_of	\N	was a business associate of	desc_right_to_left	was a business associate of	\N
95	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	taught	\N	taught	desc_left_to_right	taught	\N
96	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	taught	\N	taught	desc_right_to_left	studied under	\N
97	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	employed	\N	employed	desc_left_to_right	employed	\N
98	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	employed	\N	employed	desc_right_to_left	worked for	\N
99	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_born_in_location	\N	was born in	desc_left_to_right	was born in	\N
100	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_born_in_location	\N	was born in	desc_right_to_left	was birthplace of	\N
101	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	died_at_location	\N	died in	desc_left_to_right	died in	\N
102	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	died_at_location	\N	died in	desc_right_to_left	was place of death of	\N
103	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	acquaintance_of	\N	was an acquaintance of	desc_left_to_right	was an acquaintance of	\N
104	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	acquaintance_of	\N	was an acquaintance of	desc_right_to_left	was an acquaintance of	\N
105	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	unspecified_relationship_with	\N	unspecified relationship with	desc_left_to_right	unspecified relationship with	\N
106	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	unspecified_relationship_with	\N	unspecified relationship with	desc_right_to_left	unspecified relationship with	\N
107	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_author_of_work	\N	was author of	desc_left_to_right	was author of	\N
108	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_author_of_work	\N	was author of	desc_right_to_left	was written by	\N
109	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_copyist_of_work	\N	was copyist of	desc_left_to_right	was copyist of	\N
110	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_copyist_of_work	\N	was copyist of	desc_right_to_left	had copyist	\N
111	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_commentator_on_work	\N	was commentator on	desc_left_to_right	was commentator on	\N
112	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_commentator_on_work	\N	was commentator on	desc_right_to_left	had commentator	\N
113	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_translator_of_work	\N	was translator of	desc_left_to_right	was translator of	\N
114	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_translator_of_work	\N	was translator of	desc_right_to_left	had translator	\N
115	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_glossist_of_work	\N	was glossist of	desc_left_to_right	was glossist of	\N
116	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_glossist_of_work	\N	was glossist of	desc_right_to_left	had glossist	\N
117	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_commentary_on	\N	is commentary on	desc_left_to_right	is commentary on	\N
118	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_commentary_on	\N	is commentary on	desc_right_to_left	has commentary	\N
119	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_summary_of	\N	is summary of	desc_left_to_right	is summary of	\N
120	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_summary_of	\N	is summary of	desc_right_to_left	is summarised by	\N
121	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_continuation_of	\N	is continuation of	desc_left_to_right	is continuation of	\N
122	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_continuation_of	\N	is continuation of	desc_right_to_left	is continued by	\N
123	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	deals_with	\N	deals with	desc_left_to_right	deals with	\N
124	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	deals_with	\N	deals with	desc_right_to_left	is discussed by	\N
125	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	copied_at_place	\N	was copied at	desc_left_to_right	was copied at	\N
126	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	copied_at_place	\N	was copied at	desc_right_to_left	was place of copying of	\N
127	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	matches	\N	matches	desc_left_to_right	matches	\N
128	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	matches	\N	matches	desc_right_to_left	matches	\N
129	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_place_of_copying	\N	refers to place of_copying of	desc_left_to_right	refers to place of_copying of	\N
130	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_place_of_copying	\N	refers to place of_copying of	desc_right_to_left	has comment on place of copying	\N
131	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_title	\N	refers to title	desc_left_to_right	refers to title	\N
132	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_title	\N	refers to title	desc_right_to_left	has comment on title	\N
133	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_type_of_work	\N	refers to type of work	desc_left_to_right	refers to type of work	\N
134	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_type_of_work	\N	refers to type of work	desc_right_to_left	has comment on type of work	\N
135	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_place_of_composition	\N	refers to place of composition	desc_left_to_right	refers to place of composition	\N
136	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_place_of_composition	\N	refers to place of composition	desc_right_to_left	has comment on place of composition	\N
137	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_incipit	\N	refers to incipit	desc_left_to_right	refers to incipit	\N
138	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_incipit	\N	refers to incipit	desc_right_to_left	has comment on incipit	\N
139	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_excipit	\N	refers to excipit	desc_left_to_right	refers to excipit	\N
140	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_excipit	\N	refers to excipit	desc_right_to_left	has comment on excipit	\N
141	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_colophon	\N	refers to colophon	desc_left_to_right	refers to colophon	\N
142	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_colophon	\N	refers to colophon	desc_right_to_left	has comment on colophon	\N
143	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_dedicatees	\N	refers to dedicatees	desc_left_to_right	refers to dedicatees	\N
144	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_dedicatees	\N	refers to dedicatees	desc_right_to_left	has comment on dedicatees	\N
145	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_patrons	\N	refers to patrons	desc_left_to_right	refers to patrons	\N
146	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_patrons	\N	refers to patrons	desc_right_to_left	has comment on patrons	\N
147	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_bibliography	\N	refers to bibliography	desc_left_to_right	refers to bibliography	\N
148	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_bibliography	\N	refers to bibliography	desc_right_to_left	has comment on bibliography	\N
149	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_dedicatee_of	\N	was dedicatee of	desc_left_to_right	was dedicatee of	\N
150	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_dedicatee_of	\N	was dedicatee of	desc_right_to_left	was dedicated to	\N
151	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_patron_of	\N	was patron of	desc_left_to_right	was patron of	\N
152	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_patron_of	\N	was patron of	desc_right_to_left	had patron	\N
153	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_copyist	\N	refers to copyist	desc_left_to_right	refers to copyist	\N
154	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_copyist	\N	refers to copyist	desc_right_to_left	has comment on copyist	\N
155	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_endower_of	\N	was endower of	desc_left_to_right	was endower of	\N
156	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_endower_of	\N	was endower of	desc_right_to_left	had endower	\N
157	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_endowee_of	\N	was endowee of	desc_left_to_right	was endowee of	\N
158	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_endowee_of	\N	was endowee of	desc_right_to_left	had endowee	\N
159	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_codex	\N	refers to codex	desc_left_to_right	refers to codex	\N
160	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_codex	\N	refers to codex	desc_right_to_left	has comment on codex	\N
161	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	asked_for_copying_of	\N	asked for copying of	desc_left_to_right	asked for copying of	\N
162	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	asked_for_copying_of	\N	asked for copying of	desc_right_to_left	was copied on request from	\N
163	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	taught_text	\N	taught	desc_left_to_right	taught	\N
164	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	taught_text	\N	taught	desc_right_to_left	taught by	\N
165	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_teacher_of	\N	refers to teacher of	desc_left_to_right	refers to teacher of	\N
166	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_teacher_of	\N	refers to teacher of	desc_right_to_left	has comment on teacher of	\N
167	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	studied_text	\N	studied	desc_left_to_right	studied	\N
168	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	studied_text	\N	studied	desc_right_to_left	studied by	\N
169	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_student_of	\N	refers to student of	desc_left_to_right	refers to student of	\N
170	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_student_of	\N	refers to student of	desc_right_to_left	has comment on student of	\N
171	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_studied_in_place	\N	was studied in	desc_left_to_right	was studied in	\N
172	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	was_studied_in_place	\N	was studied in	desc_right_to_left	had students in	\N
173	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_place_studied	\N	refers to place of study of	desc_left_to_right	refers to place of study of	\N
174	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_place_studied	\N	refers to place of study of	desc_right_to_left	has comment on place of study of	\N
175	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_locations_of	\N	refers to locations of	desc_left_to_right	refers to locations of	\N
176	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_locations_of	\N	refers to locations of	desc_right_to_left	has comment on locations of	\N
177	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_relationships_of	\N	refers to relationships of	desc_left_to_right	refers to relationships of	\N
178	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_relationships_of	\N	refers to relationships of	desc_right_to_left	has comment on relationships of	\N
179	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	annotated	\N	annotated	desc_left_to_right	annotated	\N
180	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	annotated	\N	annotated	desc_right_to_left	had annotation by	\N
181	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_annotator	\N	refers to annotator of	desc_left_to_right	refers to annotator of	\N
182	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_annotator	\N	refers to annotator of	desc_right_to_left	has comment on annotator	\N
183	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_basis_texts_of	\N	refers to basis texts of	desc_left_to_right	refers to basis texts of	\N
184	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_basis_texts_of	\N	refers to basis texts of	desc_right_to_left	has note on basis texts	\N
185	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_commentaries_on	\N	refers to commentaries on	desc_left_to_right	refers to commentaries on	\N
186	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_commentaries_on	\N	refers to commentaries on	desc_right_to_left	has note on commentaries	\N
187	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_places_mentioned_in_work	\N	refers to places mentioned	desc_left_to_right	refers to places mentioned	\N
188	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_places_mentioned_in_work	\N	refers to places mentioned	desc_right_to_left	has note on places mentioned	\N
189	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_works_mentioned_in_work	\N	refers to works mentioned	desc_left_to_right	refers to works mentioned	\N
190	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_works_mentioned_in_work	\N	refers to works mentioned	desc_right_to_left	has note on works mentioned	\N
191	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_contents	\N	refers to contents of codex	desc_left_to_right	refers to contents of codex	\N
192	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_contents	\N	refers to contents of codex	desc_right_to_left	has notes on contents of codex	\N
193	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_former_owners	\N	refers to former owners	desc_left_to_right	refers to former owners	\N
194	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_former_owners	\N	refers to former owners	desc_right_to_left	has note on former owners	\N
195	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_endowers	\N	refers to endowers	desc_left_to_right	refers to endowers	\N
196	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_endowers	\N	refers to endowers	desc_right_to_left	has comment on endowers	\N
197	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_endowees	\N	refers to endowees	desc_left_to_right	refers to endowees	\N
198	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_endowees	\N	refers to endowees	desc_right_to_left	has comment on endowees	\N
199	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_gloss_on	\N	is gloss on	desc_left_to_right	is gloss on	\N
200	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_gloss_on	\N	is gloss on	desc_right_to_left	has gloss	\N
201	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_ijaza_for	\N	is ijaza for	desc_left_to_right	is ijaza for	\N
202	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_ijaza_for	\N	is ijaza for	desc_right_to_left	has ijaza	\N
203	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_versification_of	\N	is versification of	desc_left_to_right	is versification of	\N
204	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	is_versification_of	\N	is versification of	desc_right_to_left	has versification	\N
205	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_affiliations_of	\N	refers to affiliations of	desc_left_to_right	refers to affiliations of	\N
206	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_affiliations_of	\N	refers to affiliations of	desc_right_to_left	has note on affiliations	\N
207	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_members_of	\N	refers to members of	desc_left_to_right	refers to members of	\N
208	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_members_of	\N	refers to members of	desc_right_to_left	has note on members	\N
209	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_origin	\N	refers to place of origin	desc_left_to_right	refers to place of origin	\N
210	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_origin	\N	refers to place of origin	desc_right_to_left	has comment on origin	\N
211	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_destination	\N	refers to place of destination	desc_left_to_right	refers to place of destination	\N
212	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_destination	\N	refers to place of destination	desc_right_to_left	has comment on destination	\N
213	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	route	\N	route taken	desc_left_to_right	route taken	\N
214	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	route	\N	route taken	desc_right_to_left	route taken by	\N
215	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_receipt_date	\N	refers to receipt date of	desc_left_to_right	refers to receipt date of	\N
216	2018-05-30 16:24:13.199301	postgres	New	cofk_union_relationship_type	refers_to_receipt_date	\N	refers to receipt date of	desc_right_to_left	has note on receipt date	\N
\.


--
-- Data for Name: cofk_union_audit_relationship; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_audit_relationship (audit_id, change_timestamp, change_user, change_type, left_table_name, left_id_value_new, left_id_decode_new, left_id_value_old, left_id_decode_old, relationship_type, relationship_decode_left_to_right, relationship_decode_right_to_left, right_table_name, right_id_value_new, right_id_decode_new, right_id_value_old, right_id_decode_old) FROM stdin;
\.


--
-- Data for Name: cofk_union_comment; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_comment (comment_id, comment, creation_timestamp, creation_user, change_timestamp, change_user, uuid) FROM stdin;
\.


--
-- Name: cofk_union_comment_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_comment_id_seq', 1, false);


--
-- Data for Name: cofk_union_favourite_language; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_favourite_language (language_code) FROM stdin;
\.


--
-- Data for Name: cofk_union_image; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_image (image_id, image_filename, creation_timestamp, creation_user, change_timestamp, change_user, thumbnail, can_be_displayed, display_order, licence_details, licence_url, credits, uuid) FROM stdin;
\.


--
-- Name: cofk_union_image_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_image_id_seq', 1, false);


--
-- Data for Name: cofk_union_institution; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_institution (institution_id, institution_name, institution_synonyms, institution_city, institution_city_synonyms, institution_country, institution_country_synonyms, creation_timestamp, creation_user, change_timestamp, change_user, editors_notes, uuid) FROM stdin;
\.


--
-- Name: cofk_union_institution_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_institution_id_seq', 1, false);


--
-- Data for Name: cofk_union_language_of_manifestation; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_language_of_manifestation (manifestation_id, language_code, notes) FROM stdin;
\.


--
-- Data for Name: cofk_union_language_of_work; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_language_of_work (work_id, language_code, notes) FROM stdin;
\.


--
-- Data for Name: cofk_union_location; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_location (location_id, location_name, latitude, longitude, creation_timestamp, creation_user, change_timestamp, change_user, location_synonyms, editors_notes, element_1_eg_room, element_2_eg_building, element_3_eg_parish, element_4_eg_city, element_5_eg_county, element_6_eg_country, element_7_eg_empire, uuid) FROM stdin;
\.


--
-- Name: cofk_union_location_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_location_id_seq', 1, false);


--
-- Data for Name: cofk_union_manifestation; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_manifestation (manifestation_id, manifestation_type, id_number_or_shelfmark, printed_edition_details, paper_size, paper_type_or_watermark, number_of_pages_of_document, number_of_pages_of_text, seal, postage_marks, endorsements, non_letter_enclosures, manifestation_creation_calendar, manifestation_creation_date, manifestation_creation_date_gregorian, manifestation_creation_date_year, manifestation_creation_date_month, manifestation_creation_date_day, manifestation_creation_date_inferred, manifestation_creation_date_uncertain, manifestation_creation_date_approx, manifestation_is_translation, language_of_manifestation, address, manifestation_incipit, manifestation_excipit, manifestation_ps, creation_timestamp, creation_user, change_timestamp, change_user, manifestation_creation_date2_year, manifestation_creation_date2_month, manifestation_creation_date2_day, manifestation_creation_date_is_range, manifestation_creation_date_as_marked, opened, uuid, routing_mark_stamp, routing_mark_ms, handling_instructions, stored_folded, postage_costs_as_marked, postage_costs, non_delivery_reason, date_of_receipt_as_marked, manifestation_receipt_calendar, manifestation_receipt_date, manifestation_receipt_date_gregorian, manifestation_receipt_date_year, manifestation_receipt_date_month, manifestation_receipt_date_day, manifestation_receipt_date_inferred, manifestation_receipt_date_uncertain, manifestation_receipt_date_approx, manifestation_receipt_date2_year, manifestation_receipt_date2_month, manifestation_receipt_date2_day, manifestation_receipt_date_is_range, accompaniments) FROM stdin;
\.


--
-- Data for Name: cofk_union_nationality; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_nationality (nationality_id, nationality_desc) FROM stdin;
\.


--
-- Name: cofk_union_nationality_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_nationality_id_seq', 1, false);


--
-- Data for Name: cofk_union_org_type; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_org_type (org_type_id, org_type_desc) FROM stdin;
\.


--
-- Name: cofk_union_org_type_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_org_type_id_seq', 1, false);


--
-- Data for Name: cofk_union_person; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_person (person_id, foaf_name, skos_altlabel, skos_hiddenlabel, person_aliases, date_of_birth_year, date_of_birth_month, date_of_birth_day, date_of_birth, date_of_birth_inferred, date_of_birth_uncertain, date_of_birth_approx, date_of_death_year, date_of_death_month, date_of_death_day, date_of_death, date_of_death_inferred, date_of_death_uncertain, date_of_death_approx, gender, is_organisation, iperson_id, creation_timestamp, creation_user, change_timestamp, change_user, editors_notes, further_reading, organisation_type, date_of_birth_calendar, date_of_birth_is_range, date_of_birth2_year, date_of_birth2_month, date_of_birth2_day, date_of_death_calendar, date_of_death_is_range, date_of_death2_year, date_of_death2_month, date_of_death2_day, flourished, flourished_calendar, flourished_is_range, flourished_year, flourished_month, flourished_day, flourished2_year, flourished2_month, flourished2_day, uuid, flourished_inferred, flourished_uncertain, flourished_approx) FROM stdin;
\.


--
-- Name: cofk_union_person_iperson_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_person_iperson_id_seq', 1, false);


--
-- Data for Name: cofk_union_person_summary; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_person_summary (iperson_id, other_details_summary, other_details_summary_searchable, sent, recd, all_works, mentioned, role_categories, images) FROM stdin;
\.


--
-- Data for Name: cofk_union_publication; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_publication (publication_id, publication_details, change_timestamp, change_user, abbrev) FROM stdin;
\.


--
-- Name: cofk_union_publication_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_publication_id_seq', 1, false);


--
-- Data for Name: cofk_union_queryable_work; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_queryable_work (iwork_id, work_id, description, date_of_work_std, date_of_work_std_year, date_of_work_std_month, date_of_work_std_day, date_of_work_as_marked, date_of_work_inferred, date_of_work_uncertain, date_of_work_approx, creators_searchable, creators_for_display, authors_as_marked, notes_on_authors, authors_inferred, authors_uncertain, addressees_searchable, addressees_for_display, addressees_as_marked, addressees_inferred, addressees_uncertain, places_from_searchable, places_from_for_display, origin_as_marked, origin_inferred, origin_uncertain, places_to_searchable, places_to_for_display, destination_as_marked, destination_inferred, destination_uncertain, manifestations_searchable, manifestations_for_display, abstract, keywords, people_mentioned, images, related_resources, language_of_work, work_is_translation, flags, edit_status, general_notes, original_catalogue, accession_code, work_to_be_deleted, change_timestamp, change_user, drawer, editors_notes, manifestation_type, original_notes, relevant_to_cofk, subjects) FROM stdin;
\.


--
-- Data for Name: cofk_union_relationship; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_relationship (relationship_id, left_table_name, left_id_value, relationship_type, right_table_name, right_id_value, relationship_valid_from, relationship_valid_till, creation_timestamp, creation_user, change_timestamp, change_user) FROM stdin;
\.


--
-- Name: cofk_union_relationship_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_relationship_id_seq', 1, false);


--
-- Data for Name: cofk_union_relationship_type; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_relationship_type (relationship_code, desc_left_to_right, desc_right_to_left, creation_timestamp, creation_user, change_timestamp, change_user) FROM stdin;
was_involved_in	Was involved in	Affected	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
created	Created	Was created by	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
handwrote	handwrote	in hand of	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
partly_handwrote	partly handwrote	partly in hand of	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
is_rightsholder_of	Is rightsholder of	Is copyright of	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
sent	Sent	Was sent by	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
signed	Was signatory of	Was signed by	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
member_of	Member of	Includes	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
was_addressed_to	Was addressed to	Was addressee of	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
was_sent_from	Was sent from	Was source of	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
was_sent_to	Was sent to	Was destination of	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
enclosed_in	Was enclosed in	Had enclosure	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
paper_reused_for	Paper was re-used for later work	Re-used paper from earlier work	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
copied	Copied	Was copied by	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
transcribed	Transcribed	Was transcribed by	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
edited	Edited	Was edited by	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
refers_to	Refers to	Has note	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
is_manifestation_of	Is manifestation of	Has manifestation	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
is_reply_to	Is reply to	Is answered by	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
is_related_to	Is related to	Is related to	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
is_translation_of	Is translation of	Is translated by	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
is_transcription_of	Is transcription of	Is transcribed in	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
is_finding_aid_for	Is finding aid for	Has finding aid	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
quotes_from	Is quote from	Is quoted in	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
image_of	Is image of	Has image	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
mentions	Mentions	Is mentioned in	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
is_in_or_near	Is in the area of	Includes	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
stored_in	Is in repository	Has contents	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
has_flag	Has problem flagged	Flags problem in	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
formerly_owned	was former owner of	was formerly owned by	2018-05-30 16:23:23.016033	Initial import	2018-05-30 16:23:23.016033	Initial import
refers_to_author	refers to author of work	has comment on author	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_addressee	refers to addressee of work	has comment on addressee	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
intended_for	was intended for	was supposed to receive	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_date	refers to date of	has note on date	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
forms_part_of_catg	forms part of catalogue	has catalogue entry	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
mentions_work	mentions	is mentioned by	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
mentions_place	mentions	is mentioned by	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_people_mentioned_in_work	refers to people mentioned in	has comment on people mentioned	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
was_in_location	was based in or visited	had inhabitant or visitor	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
parent_of	was the parent of	was the child of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
spouse_of	was married to	was married to	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
sibling_of	was a sibling of	was a sibling of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
relative_of	was a relative of	was a relative of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
friend_of	was a friend of	was a friend of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
colleague_of	was a colleague of	was a colleague of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
collaborated_with	collaborated with	collaborated with	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
was_a_business_associate_of	was a business associate of	was a business associate of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
taught	taught	studied under	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
employed	employed	worked for	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
was_born_in_location	was born in	was birthplace of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
died_at_location	died in	was place of death of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
acquaintance_of	was an acquaintance of	was an acquaintance of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
unspecified_relationship_with	unspecified relationship with	unspecified relationship with	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
was_author_of_work	was author of	was written by	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
was_copyist_of_work	was copyist of	had copyist	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
was_commentator_on_work	was commentator on	had commentator	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
was_translator_of_work	was translator of	had translator	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
was_glossist_of_work	was glossist of	had glossist	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
is_commentary_on	is commentary on	has commentary	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
is_summary_of	is summary of	is summarised by	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
is_continuation_of	is continuation of	is continued by	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
deals_with	deals with	is discussed by	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
copied_at_place	was copied at	was place of copying of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
matches	matches	matches	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_place_of_copying	refers to place of_copying of	has comment on place of copying	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_title	refers to title	has comment on title	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_type_of_work	refers to type of work	has comment on type of work	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_place_of_composition	refers to place of composition	has comment on place of composition	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_incipit	refers to incipit	has comment on incipit	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_excipit	refers to excipit	has comment on excipit	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_colophon	refers to colophon	has comment on colophon	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_dedicatees	refers to dedicatees	has comment on dedicatees	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_patrons	refers to patrons	has comment on patrons	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_bibliography	refers to bibliography	has comment on bibliography	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
was_dedicatee_of	was dedicatee of	was dedicated to	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
was_patron_of	was patron of	had patron	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_copyist	refers to copyist	has comment on copyist	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
was_endower_of	was endower of	had endower	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
was_endowee_of	was endowee of	had endowee	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_codex	refers to codex	has comment on codex	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
asked_for_copying_of	asked for copying of	was copied on request from	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
taught_text	taught	taught by	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_teacher_of	refers to teacher of	has comment on teacher of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
studied_text	studied	studied by	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_student_of	refers to student of	has comment on student of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
was_studied_in_place	was studied in	had students in	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_place_studied	refers to place of study of	has comment on place of study of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_locations_of	refers to locations of	has comment on locations of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_relationships_of	refers to relationships of	has comment on relationships of	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
annotated	annotated	had annotation by	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_annotator	refers to annotator of	has comment on annotator	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_basis_texts_of	refers to basis texts of	has note on basis texts	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_commentaries_on	refers to commentaries on	has note on commentaries	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_places_mentioned_in_work	refers to places mentioned	has note on places mentioned	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_works_mentioned_in_work	refers to works mentioned	has note on works mentioned	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_contents	refers to contents of codex	has notes on contents of codex	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_former_owners	refers to former owners	has note on former owners	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_endowers	refers to endowers	has comment on endowers	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_endowees	refers to endowees	has comment on endowees	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
is_gloss_on	is gloss on	has gloss	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
is_ijaza_for	is ijaza for	has ijaza	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
is_versification_of	is versification of	has versification	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_affiliations_of	refers to affiliations of	has note on affiliations	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_members_of	refers to members of	has note on members	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_origin	refers to place of origin	has comment on origin	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_destination	refers to place of destination	has comment on destination	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
route	route taken	route taken by	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
refers_to_receipt_date	refers to receipt date of	has note on receipt date	2018-05-30 16:23:23.016033	postgres	2018-05-30 16:23:23.016033	postgres
\.


--
-- Data for Name: cofk_union_resource; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_resource (resource_id, resource_name, resource_details, resource_url, creation_timestamp, creation_user, change_timestamp, change_user, uuid) FROM stdin;
\.


--
-- Name: cofk_union_resource_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_resource_id_seq', 1, false);


--
-- Data for Name: cofk_union_role_category; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_role_category (role_category_id, role_category_desc) FROM stdin;
\.


--
-- Name: cofk_union_role_category_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_role_category_id_seq', 1, false);


--
-- Data for Name: cofk_union_speed_entry_text; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_speed_entry_text (speed_entry_text_id, object_type, speed_entry_text) FROM stdin;
\.


--
-- Name: cofk_union_speed_entry_text_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_speed_entry_text_id_seq', 1, false);


--
-- Data for Name: cofk_union_subject; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_subject (subject_id, subject_desc) FROM stdin;
\.


--
-- Name: cofk_union_subject_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_subject_id_seq', 1, false);


--
-- Data for Name: cofk_union_work; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_union_work (work_id, description, date_of_work_as_marked, original_calendar, date_of_work_std, date_of_work_std_gregorian, date_of_work_std_year, date_of_work_std_month, date_of_work_std_day, date_of_work2_std_year, date_of_work2_std_month, date_of_work2_std_day, date_of_work_std_is_range, date_of_work_inferred, date_of_work_uncertain, date_of_work_approx, authors_as_marked, addressees_as_marked, authors_inferred, authors_uncertain, addressees_inferred, addressees_uncertain, destination_as_marked, origin_as_marked, destination_inferred, destination_uncertain, origin_inferred, origin_uncertain, abstract, keywords, language_of_work, work_is_translation, incipit, explicit, ps, original_catalogue, accession_code, work_to_be_deleted, iwork_id, editors_notes, edit_status, relevant_to_cofk, creation_timestamp, creation_user, change_timestamp, change_user, uuid) FROM stdin;
\.


--
-- Name: cofk_union_work_iwork_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_union_work_iwork_id_seq', 1, false);


--
-- Data for Name: cofk_user_roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_user_roles (username, role_id) FROM stdin;
cofka	1
cofka	-1
\.


--
-- Data for Name: cofk_user_saved_queries; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_user_saved_queries (query_id, username, query_class, query_method, query_title, query_order_by, query_sort_descending, query_entries_per_page, query_record_layout, query_menu_item_name, creation_timestamp) FROM stdin;
-1	cofka	editable_work	db_search	Data presentation options for latest query	date_of_work_std	0	100	across_page	Search works (compact view)	2018-05-30 12:23:29.235773
\.


--
-- Name: cofk_user_saved_queries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_user_saved_queries_id_seq', 1, true);


--
-- Data for Name: cofk_user_saved_query_selection; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_user_saved_query_selection (selection_id, query_id, column_name, column_value, op_name, op_value, column_value2) FROM stdin;
\.


--
-- Name: cofk_user_saved_query_selection_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_user_saved_query_selection_id_seq', 1, false);


--
-- Data for Name: cofk_users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cofk_users (username, pw, surname, forename, failed_logins, login_time, prev_login, active, email) FROM stdin;
cofka	1349c62091000149881949fbc5615e5c	Administrator	Admin	0	2018-05-30 12:19:30.723817	\N	1	nowhere@example.com
\.


--
-- Name: cofk_users_username_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cofk_users_username_seq', 1, false);


--
-- Data for Name: copy_cofk_union_queryable_work; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.copy_cofk_union_queryable_work (iwork_id, work_id, description, date_of_work_std, date_of_work_std_year, date_of_work_std_month, date_of_work_std_day, date_of_work_as_marked, date_of_work_inferred, date_of_work_uncertain, date_of_work_approx, creators_searchable, creators_for_display, authors_as_marked, notes_on_authors, authors_inferred, authors_uncertain, addressees_searchable, addressees_for_display, addressees_as_marked, addressees_inferred, addressees_uncertain, places_from_searchable, places_from_for_display, origin_as_marked, origin_inferred, origin_uncertain, places_to_searchable, places_to_for_display, destination_as_marked, destination_inferred, destination_uncertain, manifestations_searchable, manifestations_for_display, abstract, keywords, people_mentioned, images, related_resources, language_of_work, work_is_translation, flags, edit_status, general_notes, original_catalogue, accession_code, work_to_be_deleted, change_timestamp, change_user, drawer, editors_notes, manifestation_type, original_notes, relevant_to_cofk, subjects) FROM stdin;
\.


--
-- Data for Name: iso_639_language_codes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.iso_639_language_codes (code_639_3, code_639_1, language_name, language_id) FROM stdin;
aaa		Ghotuo	1
aab		Alumu-Tesu	2
aac		Ari	3
aad		Amal	4
aae		Arbëreshë Albanian	5
aaf		Aranadan	6
aag		Ambrak	7
aah		Abu' Arapesh	8
aai		Arifama-Miniafia	9
aak		Ankave	10
aal		Afade	11
aam		Aramanik	12
aan		Anambé	13
aao		Algerian Saharan Arabic	14
aap		Pará Arára	15
aaq		Eastern Abnaki	16
aar	aa	Afar	17
aas		Aasáx	18
aat		Arvanitika Albanian	19
aau		Abau	20
aaw		Solong	21
aax		Mandobo Atas	22
aaz		Amarasi	23
aba		Abé	24
abb		Bankon	25
abc		Ambala Ayta	26
abd		Manide	27
abe		Western Abnaki	28
abf		Abai Sungai	29
abg		Abaga	30
abh		Tajiki Arabic	31
abi		Abidji	32
abj		Aka-Bea	33
abk	ab	Abkhazian	34
abl		Lampung Nyo	35
abm		Abanyom	36
abn		Abua	37
abo		Abon	38
abp		Abellen Ayta	39
abq		Abaza	40
abr		Abron	41
abs		Ambonese Malay	42
abt		Ambulas	43
abu		Abure	44
abv		Baharna Arabic	45
abw		Pal	46
abx		Inabaknon	47
aby		Aneme Wake	48
abz		Abui	49
aca		Achagua	50
acb		Áncá	51
acd		Gikyode	52
ace		Achinese	53
acf		Saint Lucian Creole French	54
ach		Acoli	55
aci		Aka-Cari	56
ack		Aka-Kora	57
acl		Akar-Bale	58
acm		Mesopotamian Arabic	59
acn		Achang	60
acp		Eastern Acipa	61
acq		Ta'izzi-Adeni Arabic	62
acr		Achi	63
acs		Acroá	64
act		Achterhoeks	65
acu		Achuar-Shiwiar	66
acv		Achumawi	67
acw		Hijazi Arabic	68
acx		Omani Arabic	69
acy		Cypriot Arabic	70
acz		Acheron	71
ada		Adangme	72
adb		Adabe	73
add		Dzodinka	74
ade		Adele	75
adf		Dhofari Arabic	76
adg		Andegerebinha	77
adh		Adhola	78
adi		Adi	79
adj		Adioukrou	80
adl		Galo	81
adn		Adang	82
ado		Abu	83
adp		Adap	84
adq		Adangbe	85
adr		Adonara	86
ads		Adamorobe Sign Language	87
adt		Adnyamathanha	88
adu		Aduge	89
adw		Amundava	90
adx		Amdo Tibetan	91
ady		Adyghe	92
adz		Adzera	93
aea		Areba	94
aeb		Tunisian Arabic	95
aec		Saidi Arabic	96
aed		Argentine Sign Language	97
aee		Northeast Pashayi	98
aek		Haeke	99
ael		Ambele	100
aem		Arem	101
aen		Armenian Sign Language	102
aeq		Aer	103
aer		Eastern Arrernte	104
aes		Alsea	105
aeu		Akeu	106
aew		Ambakich	107
aey		Amele	108
aez		Aeka	109
afb		Gulf Arabic	110
afd		Andai	111
afe		Putukwam	112
afg		Afghan Sign Language	113
afh		Afrihili	114
afi		Akrukay	115
afk		Nanubae	116
afn		Defaka	117
afo		Eloyi	118
afp		Tapei	119
afr	af	Afrikaans	120
afs		Afro-Seminole Creole	121
aft		Afitti	122
afu		Awutu	123
afz		Obokuitai	124
aga		Aguano	125
agb		Legbo	126
agc		Agatu	127
agd		Agarabi	128
age		Angal	129
agf		Arguni	130
agg		Angor	131
agh		Ngelima	132
agi		Agariya	133
agj		Argobba	134
agk		Isarog Agta	135
agl		Fembe	136
agm		Angaataha	137
agn		Agutaynen	138
ago		Tainae	139
agq		Aghem	140
agr		Aguaruna	141
ags		Esimbi	142
agt		Central Cagayan Agta	143
agu		Aguacateco	144
agv		Remontado Dumagat	145
agw		Kahua	146
agx		Aghul	147
agy		Southern Alta	148
agz		Mt. Iriga Agta	149
aha		Ahanta	150
ahb		Axamb	151
ahg		Qimant	152
ahh		Aghu	153
ahi		Tiagbamrin Aizi	154
ahk		Akha	155
ahl		Igo	156
ahm		Mobumrin Aizi	157
ahn		Àhàn	158
aho		Ahom	159
ahp		Aproumu Aizi	160
ahr		Ahirani	161
ahs		Ashe	162
aht		Ahtena	163
aia		Arosi	164
aib		Ainu (China)	165
aic		Ainbai	166
aid		Alngith	167
aie		Amara	168
aif		Agi	169
aig		Antigua and Barbuda Creole English	170
aih		Ai-Cham	171
aii		Assyrian Neo-Aramaic	172
aij		Lishanid Noshan	173
aik		Ake	174
ail		Aimele	175
aim		Aimol	176
ain		Ainu (Japan)	177
aio		Aiton	178
aip		Burumakok	179
aiq		Aimaq	180
air		Airoran	181
ais		Nataoran Amis	182
ait		Arikem	183
aiw		Aari	184
aix		Aighon	185
aiy		Ali	186
aja		Aja (Sudan)	187
ajg		Aja (Benin)	188
aji		Ajië	189
ajp		South Levantine Arabic	190
ajt		Judeo-Tunisian Arabic	191
aju		Judeo-Moroccan Arabic	192
ajw		Ajawa	193
ajz		Amri Karbi	194
aka	ak	Akan	195
akb		Batak Angkola	196
akc		Mpur	197
akd		Ukpet-Ehom	198
ake		Akawaio	199
akf		Akpa	200
akg		Anakalangu	201
akh		Angal Heneng	202
aki		Aiome	203
akj		Aka-Jeru	204
akk		Akkadian	205
akl		Aklanon	206
akm		Aka-Bo	207
ako		Akurio	208
akp		Siwu	209
akq		Ak	210
akr		Araki	211
aks		Akaselem	212
akt		Akolet	213
aku		Akum	214
akv		Akhvakh	215
akw		Akwa	216
akx		Aka-Kede	217
aky		Aka-Kol	218
akz		Alabama	219
ala		Alago	220
alc		Qawasqar	221
ald		Alladian	222
ale		Aleut	223
alf		Alege	224
alh		Alawa	225
ali		Amaimon	226
alj		Alangan	227
alk		Alak	228
all		Allar	229
alm		Amblong	230
aln		Gheg Albanian	231
alo		Larike-Wakasihu	232
alp		Alune	233
alq		Algonquin	234
alr		Alutor	235
als		Tosk Albanian	236
alt		Southern Altai	237
alu		'Are'are	238
alw		Alaba-K’abeena	239
alx		Amol	240
aly		Alyawarr	241
alz		Alur	242
ama		Amanayé	243
amb		Ambo	244
amc		Amahuaca	245
ame		Yanesha'	246
amf		Hamer-Banna	247
amg		Amarag	248
amh	am	Amharic	249
ami		Amis	250
amj		Amdang	251
amk		Ambai	252
aml		War-Jaintia	253
amm		Ama (Papua New Guinea)	254
amn		Amanab	255
amo		Amo	256
amp		Alamblak	257
amq		Amahai	258
amr		Amarakaeri	259
ams		Southern Amami-Oshima	260
amt		Amto	261
amu		Guerrero Amuzgo	262
amv		Ambelau	263
amw		Western Neo-Aramaic	264
amx		Anmatyerre	265
amy		Ami	266
amz		Atampaya	267
ana		Andaqui	268
anb		Andoa	269
anc		Ngas	270
and		Ansus	271
ane		Xârâcùù	272
anf		Animere	273
ang		Old English (ca. 450-1100)	274
anh		Nend	275
ani		Andi	276
anj		Anor	277
ank		Goemai	278
anl		Anu	279
anm		Anal	280
ann		Obolo	281
ano		Andoque	282
anp		Angika	283
anq		Jarawa (India)	284
anr		Andh	285
ans		Anserma	286
ant		Antakarinya	287
anu		Anuak	288
anv		Denya	289
anw		Anaang	290
anx		Andra-Hus	291
any		Anyin	292
anz		Anem	293
aoa		Angolar	294
aob		Abom	295
aoc		Pemon	296
aod		Andarum	297
aoe		Angal Enen	298
aof		Bragat	299
aog		Angoram	300
aoh		Arma	301
aoi		Anindilyakwa	302
aoj		Mufian	303
aok		Arhö	304
aol		Alor	305
aom		Ömie	306
aon		Bumbita Arapesh	307
aor		Aore	308
aos		Taikat	309
aot		A'tong	310
aox		Atorada	311
aoz		Uab Meto	312
apb		Sa'a	313
apc		North Levantine Arabic	314
apd		Sudanese Arabic	315
ape		Bukiyip	316
apf		Pahanan Agta	317
apg		Ampanang	318
aph		Athpariya	319
api		Apiaká	320
apj		Jicarilla Apache	321
apk		Kiowa Apache	322
apl		Lipan Apache	323
apm		Mescalero-Chiricahua Apache	324
apn		Apinayé	325
apo		Apalik	326
app		Apma	327
apq		A-Pucikwar	328
apr		Arop-Lokep	329
aps		Arop-Sissano	330
apt		Apatani	331
apu		Apurinã	332
apv		Alapmunte	333
apw		Western Apache	334
apx		Aputai	335
apy		Apalaí	336
apz		Safeyoka	337
aqc		Archi	338
aqg		Arigidi	339
aqm		Atohwaim	340
aqn		Northern Alta	341
aqp		Atakapa	342
aqr		Arhâ	343
aqz		Akuntsu	344
ara	ar	Arabic	345
arb		Standard Arabic	346
arc		Official Aramaic (700-300 BCE)	347
ard		Arabana	348
are		Western Arrarnta	349
arg	an	Aragonese	350
arh		Arhuaco	351
ari		Arikara	352
arj		Arapaso	353
ark		Arikapú	354
arl		Arabela	355
arn		Mapudungun	356
aro		Araona	357
arp		Arapaho	358
arq		Algerian Arabic	359
arr		Karo (Brazil)	360
ars		Najdi Arabic	361
aru		Aruá (Amazonas State)	362
arv		Arbore	363
arw		Arawak	364
arx		Aruá (Rodonia State)	365
ary		Moroccan Arabic	366
arz		Egyptian Arabic	367
asa		Asu (Tanzania)	368
asb		Assiniboine	369
asc		Casuarina Coast Asmat	370
asd		Asas	371
ase		American Sign Language	372
asf		Australian Sign Language	373
asg		Cishingini	374
ash		Abishira	375
asi		Buruwai	376
asj		Nsari	377
ask		Ashkun	378
asl		Asilulu	379
asm	as	Assamese	380
asn		Xingú Asuriní	381
aso		Dano	382
asp		Algerian Sign Language	383
asq		Austrian Sign Language	384
asr		Asuri	385
ass		Ipulo	386
ast		Asturian	387
asu		Tocantins Asurini	388
asv		Asoa	389
asw		Australian Aborigines Sign Language	390
asx		Muratayak	391
asy		Yaosakor Asmat	392
asz		As	393
ata		Pele-Ata	394
atb		Zaiwa	395
atc		Atsahuaca	396
atd		Ata Manobo	397
ate		Atemble	398
atg		Ivbie North-Okpela-Arhe	399
ati		Attié	400
atj		Atikamekw	401
atk		Ati	402
atl		Mt. Iraya Agta	403
atm		Ata	404
atn		Ashtiani	405
ato		Atong	406
atp		Pudtol Atta	407
atq		Aralle-Tabulahan	408
atr		Waimiri-Atroari	409
ats		Gros Ventre	410
att		Pamplona Atta	411
atu		Reel	412
atv		Northern Altai	413
atw		Atsugewi	414
atx		Arutani	415
aty		Aneityum	416
atz		Arta	417
aua		Asumboa	418
aub		Alugu	419
auc		Waorani	420
aud		Anuta	421
aue		=/Kx'au//'ein	422
aug		Aguna	423
auh		Aushi	424
aui		Anuki	425
auj		Awjilah	426
auk		Heyo	427
aul		Aulua	428
aum		Asu (Nigeria)	429
aun		Molmo One	430
auo		Auyokawa	431
aup		Makayam	432
auq		Anus	433
aur		Aruek	434
aut		Austral	435
auu		Auye	436
auw		Awyi	437
aux		Aurá	438
auy		Awiyaana	439
auz		Uzbeki Arabic	440
ava	av	Avaric	441
avb		Avau	442
avd		Alviri-Vidari	443
ave	ae	Avestan	444
avi		Avikam	445
avk		Kotava	446
avl		Eastern Egyptian Bedawi Arabic	447
avn		Avatime	448
avo		Agavotaguerra	449
avs		Aushiri	450
avt		Au	451
avu		Avokaya	452
avv		Avá-Canoeiro	453
awa		Awadhi	454
awb		Awa (Papua New Guinea)	455
awc		Cicipu	456
awe		Awetí	457
awh		Awbono	458
awi		Aekyom	459
awk		Awabakal	460
awm		Arawum	461
awn		Awngi	462
awo		Awak	463
awr		Awera	464
aws		South Awyu	465
awt		Araweté	466
awu		Central Awyu	467
awv		Jair Awyu	468
aww		Awun	469
awx		Awara	470
awy		Edera Awyu	471
axb		Abipon	472
axg		Mato Grosso Arára	473
axk		Yaka (Central African Republic)	474
axm		Middle Armenian	475
axx		Xaragure	476
aya		Awar	477
ayb		Ayizo Gbe	478
ayc		Southern Aymara	479
ayd		Ayabadhu	480
aye		Ayere	481
ayg		Ginyanga	482
ayh		Hadrami Arabic	483
ayi		Leyigha	484
ayk		Akuku	485
ayl		Libyan Arabic	486
aym	ay	Aymara	487
ayn		Sanaani Arabic	488
ayo		Ayoreo	489
ayp		North Mesopotamian Arabic	490
ayq		Ayi (Papua New Guinea)	491
ayr		Central Aymara	492
ays		Sorsogon Ayta	493
ayt		Magbukun Ayta	494
ayu		Ayu	495
ayx		Ayi (China)	496
ayy		Tayabas Ayta	497
ayz		Mai Brat	498
aza		Azha	499
azb		South Azerbaijani	500
aze	az	Azerbaijani	501
azg		San Pedro Amuzgos Amuzgo	502
azj		North Azerbaijani	503
azm		Ipalapa Amuzgo	504
azo		Awing	505
azt		Faire Atta	506
azz		Highland Puebla Nahuatl	507
baa		Babatana	508
bab		Bainouk-Gunyuño	509
bac		Badui	510
bae		Baré	511
baf		Nubaca	512
bag		Tuki	513
bah		Bahamas Creole English	514
baj		Barakai	515
bak	ba	Bashkir	516
bal		Baluchi	517
bam	bm	Bambara	518
ban		Balinese	519
bao		Waimaha	520
bap		Bantawa	521
bar		Bavarian	522
bas		Basa (Cameroon)	523
bau		Bada (Nigeria)	524
bav		Vengo	525
baw		Bambili-Bambui	526
bax		Bamun	527
bay		Batuley	528
baz		Tunen	529
bba		Baatonum	530
bbb		Barai	531
bbc		Batak Toba	532
bbd		Bau	533
bbe		Bangba	534
bbf		Baibai	535
bbg		Barama	536
bbh		Bugan	537
bbi		Barombi	538
bbj		Ghomálá'	539
bbk		Babanki	540
bbl		Bats	541
bbm		Babango	542
bbn		Uneapa	543
bbo		Northern Bobo Madaré	544
bbp		West Central Banda	545
bbq		Bamali	546
bbr		Girawa	547
bbs		Bakpinka	548
bbt		Mburku	549
bbu		Kulung (Nigeria)	550
bbv		Karnai	551
bbw		Baba	552
bbx		Bubia	553
bby		Befang	554
bbz		Babalia Creole Arabic	555
bca		Central Bai	556
bcb		Bainouk-Samik	557
bcc		Southern Balochi	558
bcd		North Babar	559
bce		Bamenyam	560
bcf		Bamu	561
bcg		Baga Binari	562
bch		Bariai	563
bci		Baoulé	564
bcj		Bardi	565
bck		Bunaba	566
bcl		Central Bicolano	567
bcm		Bannoni	568
bcn		Bali (Nigeria)	569
bco		Kaluli	570
bcp		Bali (Democratic Republic of Congo)	571
bcq		Bench	572
bcr		Babine	573
bcs		Kohumono	574
bct		Bendi	575
bcu		Awad Bing	576
bcv		Shoo-Minda-Nye	577
bcw		Bana	578
bcy		Bacama	579
bcz		Bainouk-Gunyaamolo	580
bda		Bayot	581
bdb		Basap	582
bdc		Emberá-Baudó	583
bdd		Bunama	584
bde		Bade	585
bdf		Biage	586
bdg		Bonggi	587
bdh		Baka (Sudan)	588
bdi		Burun	589
bdj		Bai	590
bdk		Budukh	591
bdl		Indonesian Bajau	592
bdm		Buduma	593
bdn		Baldemu	594
bdo		Morom	595
bdp		Bende	596
bdq		Bahnar	597
bdr		West Coast Bajau	598
bds		Burunge	599
bdt		Bokoto	600
bdu		Oroko	601
bdv		Bodo Parja	602
bdw		Baham	603
bdx		Budong-Budong	604
bdy		Bandjalang	605
bdz		Badeshi	606
bea		Beaver	607
beb		Bebele	608
bec		Iceve-Maci	609
bed		Bedoanas	610
bee		Byangsi	611
bef		Benabena	612
beg		Belait	613
beh		Biali	614
bei		Bekati'	615
bej		Beja	616
bek		Bebeli	617
bel	be	Belarusian	618
bem		Bemba (Zambia)	619
ben	bn	Bengali	620
beo		Beami	621
bep		Besoa	622
beq		Beembe	623
bes		Besme	624
bet		Guiberoua Béte	625
beu		Blagar	626
bev		Daloa Bété	627
bew		Betawi	628
bex		Jur Modo	629
bey		Beli (Papua New Guinea)	630
bez		Bena (Tanzania)	631
bfa		Bari	632
bfb		Pauri Bareli	633
bfc		Northern Bai	634
bfd		Bafut	635
bfe		Betaf	636
bff		Bofi	637
bfg		Busang Kayan	638
bfh		Blafe	639
bfi		British Sign Language	640
bfj		Bafanji	641
bfk		Ban Khor Sign Language	642
bfl		Banda-Ndélé	643
bfm		Mmen	644
bfn		Bunak	645
bfo		Malba Birifor	646
bfp		Beba	647
bfq		Badaga	648
bfr		Bazigar	649
bfs		Southern Bai	650
bft		Balti	651
bfu		Gahri	652
bfw		Bondo	653
bfx		Bantayanon	654
bfy		Bagheli	655
bfz		Mahasu Pahari	656
bga		Gwamhi-Wuri	657
bgb		Bobongko	658
bgc		Haryanvi	659
bgd		Rathwi Bareli	660
bge		Bauria	661
bgf		Bangandu	662
bgg		Bugun	663
bgi		Giangan	664
bgj		Bangolan	665
bgk		Bit	666
bgl		Bo (Laos)	667
bgm		Baga Mboteni	668
bgn		Western Balochi	669
bgo		Baga Koga	670
bgp		Eastern Balochi	671
bgq		Bagri	672
bgr		Bawm Chin	673
bgs		Tagabawa	674
bgt		Bughotu	675
bgu		Mbongno	676
bgv		Warkay-Bipim	677
bgw		Bhatri	678
bgx		Balkan Gagauz Turkish	679
bgy		Benggoi	680
bgz		Banggai	681
bha		Bharia	682
bhb		Bhili	683
bhc		Biga	684
bhd		Bhadrawahi	685
bhe		Bhaya	686
bhf		Odiai	687
bhg		Binandere	688
bhh		Bukharic	689
bhi		Bhilali	690
bhj		Bahing	691
bhl		Bimin	692
bhm		Bathari	693
bhn		Bohtan Neo-Aramaic	694
bho		Bhojpuri	695
bhp		Bima	696
bhq		Tukang Besi South	697
bhr		Bara Malagasy	698
bhs		Buwal	699
bht		Bhattiyali	700
bhu		Bhunjia	701
bhv		Bahau	702
bhw		Biak	703
bhx		Bhalay	704
bhy		Bhele	705
bhz		Bada (Indonesia)	706
bia		Badimaya	707
bib		Bissa	708
bic		Bikaru	709
bid		Bidiyo	710
bie		Bepour	711
bif		Biafada	712
big		Biangai	713
bij		Vaghat-Ya-Bijim-Legeri	714
bik		Bikol	715
bil		Bile	716
bim		Bimoba	717
bin		Bini	718
bio		Nai	719
bip		Bila	720
biq		Bipi	721
bir		Bisorio	722
bis	bi	Bislama	723
bit		Berinomo	724
biu		Biete	725
biv		Southern Birifor	726
biw		Kol (Cameroon)	727
bix		Bijori	728
biy		Birhor	729
biz		Baloi	730
bja		Budza	731
bjb		Banggarla	732
bjc		Bariji	733
bjd		Bandjigali	734
bje		Biao-Jiao Mien	735
bjf		Barzani Jewish Neo-Aramaic	736
bjg		Bidyogo	737
bjh		Bahinemo	738
bji		Burji	739
bjj		Kanauji	740
bjk		Barok	741
bjl		Bulu (Papua New Guinea)	742
bjm		Bajelani	743
bjn		Banjar	744
bjo		Mid-Southern Banda	745
bjq		Southern Betsimisaraka Malagasy	746
bjr		Binumarien	747
bjs		Bajan	748
bjt		Balanta-Ganja	749
bju		Busuu	750
bjv		Bedjond	751
bjw		Bakwé	752
bjx		Banao Itneg	753
bjy		Bayali	754
bjz		Baruga	755
bka		Kyak	756
bkc		Baka (Cameroon)	757
bkd		Binukid	758
bkf		Beeke	759
bkg		Buraka	760
bkh		Bakoko	761
bki		Baki	762
bkj		Pande	763
bkk		Brokskat	764
bkl		Berik	765
bkm		Kom (Cameroon)	766
bkn		Bukitan	767
bko		Kwa'	768
bkp		Boko (Democratic Republic of Congo)	769
bkq		Bakairí	770
bkr		Bakumpai	771
bks		Northern Sorsoganon	772
bkt		Boloki	773
bku		Buhid	774
bkv		Bekwarra	775
bkw		Bekwil	776
bkx		Baikeno	777
bky		Bokyi	778
bkz		Bungku	779
bla		Siksika	780
blb		Bilua	781
blc		Bella Coola	782
bld		Bolango	783
ble		Balanta-Kentohe	784
blf		Buol	785
blg		Balau	786
blh		Kuwaa	787
bli		Bolia	788
blj		Bolongan	789
blk		Pa'o Karen	790
bll		Biloxi	791
blm		Beli (Sudan)	792
bln		Southern Catanduanes Bicolano	793
blo		Anii	794
blp		Blablanga	795
blq		Baluan-Pam	796
blr		Blang	797
bls		Balaesang	798
blt		Tai Dam	799
blv		Bolo	800
blw		Balangao	801
blx		Mag-Indi Ayta	802
bly		Notre	803
blz		Balantak	804
bma		Lame	805
bmb		Bembe	806
bmc		Biem	807
bmd		Baga Manduri	808
bme		Limassa	809
bmf		Bom	810
bmg		Bamwe	811
bmh		Kein	812
bmi		Bagirmi	813
bmj		Bote-Majhi	814
bmk		Ghayavi	815
bml		Bomboli	816
bmm		Northern Betsimisaraka Malagasy	817
bmn		Bina (Papua New Guinea)	818
bmo		Bambalang	819
bmp		Bulgebi	820
bmq		Bomu	821
bmr		Muinane	822
bms		Bilma Kanuri	823
bmt		Biao Mon	824
bmu		Burum-Mindik	825
bmv		Bum	826
bmw		Bomwali	827
bmx		Baimak	828
bmy		Bemba (Democratic Republic of Congo)	829
bmz		Baramu	830
bna		Bonerate	831
bnb		Bookan	832
bnc		Bontok	833
bnd		Banda (Indonesia)	834
bne		Bintauna	835
bnf		Masiwang	836
bng		Benga	837
bni		Bangi	838
bnj		Eastern Tawbuid	839
bnk		Bierebo	840
bnl		Boon	841
bnm		Batanga	842
bnn		Bunun	843
bno		Bantoanon	844
bnp		Bola	845
bnq		Bantik	846
bnr		Butmas-Tur	847
bns		Bundeli	848
bnu		Bentong	849
bnv		Bonerif	850
bnw		Bisis	851
bnx		Bangubangu	852
bny		Bintulu	853
bnz		Beezen	854
boa		Bora	855
bob		Aweer	856
bod	bo	Tibetan	857
boe		Mundabli	858
bof		Bolon	859
bog		Bamako Sign Language	860
boh		Boma	861
boi		Barbareño	862
boj		Anjam	863
bok		Bonjo	864
bol		Bole	865
bom		Berom	866
bon		Bine	867
boo		Tiemacèwè Bozo	868
bop		Bonkiman	869
boq		Bogaya	870
bor		Borôro	871
bos	bs	Bosnian	872
bot		Bongo	873
bou		Bondei	874
bov		Tuwuli	875
bow		Rema	876
box		Buamu	877
boy		Bodo (Central African Republic)	878
boz		Tiéyaxo Bozo	879
bpa		Dakaka	880
bpb		Barbacoas	881
bpd		Banda-Banda	882
bpg		Bonggo	883
bph		Botlikh	884
bpi		Bagupi	885
bpj		Binji	886
bpk		Orowe	887
bpl		Broome Pearling Lugger Pidgin	888
bpm		Biyom	889
bpn		Dzao Min	890
bpo		Anasi	891
bpp		Kaure	892
bpq		Banda Malay	893
bpr		Koronadal Blaan	894
bps		Sarangani Blaan	895
bpt		Barrow Point	896
bpu		Bongu	897
bpv		Bian Marind	898
bpw		Bo (Papua New Guinea)	899
bpx		Palya Bareli	900
bpy		Bishnupriya	901
bpz		Bilba	902
bqa		Tchumbuli	903
bqb		Bagusa	904
bqc		Boko (Benin)	905
bqd		Bung	906
bqf		Baga Kaloum	907
bqg		Bago-Kusuntu	908
bqh		Baima	909
bqi		Bakhtiari	910
bqj		Bandial	911
bqk		Banda-Mbrès	912
bql		Bilakura	913
bqm		Wumboko	914
bqn		Bulgarian Sign Language	915
bqo		Balo	916
bqp		Busa	917
bqq		Biritai	918
bqr		Burusu	919
bqs		Bosngun	920
bqt		Bamukumbit	921
bqu		Boguru	922
bqv		Begbere-Ejar	923
bqw		Buru (Nigeria)	924
bqx		Baangi	925
bqy		Bengkala Sign Language	926
bqz		Bakaka	927
bra		Braj	928
brb		Lave	929
brc		Berbice Creole Dutch	930
brd		Baraamu	931
bre	br	Breton	932
brf		Bera	933
brg		Baure	934
brh		Brahui	935
bri		Mokpwe	936
brj		Bieria	937
brk		Birked	938
brl		Birwa	939
brm		Barambu	940
brn		Boruca	941
bro		Brokkat	942
brp		Barapasi	943
brq		Breri	944
brr		Birao	945
brs		Baras	946
brt		Bitare	947
bru		Eastern Bru	948
brv		Western Bru	949
brw		Bellari	950
brx		Bodo (India)	951
bry		Burui	952
brz		Bilbil	953
bsa		Abinomn	954
bsb		Brunei Bisaya	955
bsc		Bassari	956
bse		Wushi	957
bsf		Bauchi	958
bsg		Bashkardi	959
bsh		Kati	960
bsi		Bassossi	961
bsj		Bangwinji	962
bsk		Burushaski	963
bsl		Basa-Gumna	964
bsm		Busami	965
bsn		Barasana-Eduria	966
bso		Buso	967
bsp		Baga Sitemu	968
bsq		Bassa	969
bsr		Bassa-Kontagora	970
bss		Akoose	971
bst		Basketo	972
bsu		Bahonsuai	973
bsv		Baga Sobané	974
bsw		Baiso	975
bsx		Yangkam	976
bsy		Sabah Bisaya	977
bta		Bata	978
btc		Bati (Cameroon)	979
btd		Batak Dairi	980
bte		Gamo-Ningi	981
btf		Birgit	982
btg		Gagnoa Bété	983
bth		Biatah Bidayuh	984
bti		Burate	985
btj		Bacanese Malay	986
btl		Bhatola	987
btm		Batak Mandailing	988
btn		Ratagnon	989
bto		Rinconada Bikol	990
btp		Budibud	991
btq		Batek	992
btr		Baetora	993
bts		Batak Simalungun	994
btt		Bete-Bendi	995
btu		Batu	996
btv		Bateri	997
btw		Butuanon	998
btx		Batak Karo	999
bty		Bobot	1000
btz		Batak Alas-Kluet	1001
bua		Buriat	1002
bub		Bua	1003
buc		Bushi	1004
bud		Ntcham	1005
bue		Beothuk	1006
buf		Bushoong	1007
bug		Buginese	1008
buh		Younuo Bunu	1009
bui		Bongili	1010
buj		Basa-Gurmana	1011
buk		Bugawac	1012
bul	bg	Bulgarian	1013
bum		Bulu (Cameroon)	1014
bun		Sherbro	1015
buo		Terei	1016
bup		Busoa	1017
buq		Brem	1018
bus		Bokobaru	1019
but		Bungain	1020
buu		Budu	1021
buv		Bun	1022
buw		Bubi	1023
bux		Boghom	1024
buy		Bullom So	1025
buz		Bukwen	1026
bva		Barein	1027
bvb		Bube	1028
bvc		Baelelea	1029
bvd		Baeggu	1030
bve		Berau Malay	1031
bvf		Boor	1032
bvg		Bonkeng	1033
bvh		Bure	1034
bvi		Belanda Viri	1035
bvj		Baan	1036
bvk		Bukat	1037
bvl		Bolivian Sign Language	1038
bvm		Bamunka	1039
bvn		Buna	1040
bvo		Bolgo	1041
bvq		Birri	1042
bvr		Burarra	1043
bvt		Bati (Indonesia)	1044
bvu		Bukit Malay	1045
bvv		Baniva	1046
bvw		Boga	1047
bvx		Dibole	1048
bvy		Baybayanon	1049
bvz		Bauzi	1050
bwa		Bwatoo	1051
bwb		Namosi-Naitasiri-Serua	1052
bwc		Bwile	1053
bwd		Bwaidoka	1054
bwe		Bwe Karen	1055
bwf		Boselewa	1056
bwg		Barwe	1057
bwh		Bishuo	1058
bwi		Baniwa	1059
bwj		Láá Láá Bwamu	1060
bwk		Bauwaki	1061
bwl		Bwela	1062
bwm		Biwat	1063
bwn		Wunai Bunu	1064
bwo		Boro (Ethiopia)	1065
bwp		Mandobo Bawah	1066
bwq		Southern Bobo Madaré	1067
bwr		Bura-Pabir	1068
bws		Bomboma	1069
bwt		Bafaw-Balong	1070
bwu		Buli (Ghana)	1071
bww		Bwa	1072
bwx		Bu-Nao Bunu	1073
bwy		Cwi Bwamu	1074
bwz		Bwisi	1075
bxa		Bauro	1076
bxb		Belanda Bor	1077
bxc		Molengue	1078
bxd		Pela	1079
bxe		Birale	1080
bxf		Bilur	1081
bxg		Bangala	1082
bxh		Buhutu	1083
bxi		Pirlatapa	1084
bxj		Bayungu	1085
bxk		Bukusu	1086
bxl		Jalkunan	1087
bxm		Mongolia Buriat	1088
bxn		Burduna	1089
bxo		Barikanchi	1090
bxp		Bebil	1091
bxq		Beele	1092
bxr		Russia Buriat	1093
bxs		Busam	1094
bxu		China Buriat	1095
bxv		Berakou	1096
bxw		Bankagooma	1097
bxx		Borna (Democratic Republic of Congo)	1098
bxz		Binahari	1099
bya		Batak	1100
byb		Bikya	1101
byc		Ubaghara	1102
byd		Benyadu'	1103
bye		Pouye	1104
byf		Bete	1105
byg		Baygo	1106
byh		Bhujel	1107
byi		Buyu	1108
byj		Bina (Nigeria)	1109
byk		Biao	1110
byl		Bayono	1111
bym		Bidyara	1112
byn		Bilin	1113
byo		Biyo	1114
byp		Bumaji	1115
byq		Basay	1116
byr		Baruya	1117
bys		Burak	1118
byt		Berti	1119
byv		Medumba	1120
byw		Belhariya	1121
byx		Qaqet	1122
byy		Buya	1123
byz		Banaro	1124
bza		Bandi	1125
bzb		Andio	1126
bzd		Bribri	1127
bze		Jenaama Bozo	1128
bzf		Boikin	1129
bzg		Babuza	1130
bzh		Mapos Buang	1131
bzi		Bisu	1132
bzj		Belize Kriol English	1133
bzk		Nicaragua Creole English	1134
bzl		Boano (Sulawesi)	1135
bzm		Bolondo	1136
bzn		Boano (Maluku)	1137
bzo		Bozaba	1138
bzp		Kemberano	1139
bzq		Buli (Indonesia)	1140
bzr		Biri	1141
bzs		Brazilian Sign Language	1142
bzt		Brithenig	1143
bzu		Burmeso	1144
bzv		Bebe	1145
bzw		Basa (Nigeria)	1146
bzx		Hainyaxo Bozo	1147
bzy		Obanliku	1148
bzz		Evant	1149
caa		Chortí	1150
cab		Garifuna	1151
cac		Chuj	1152
cad		Caddo	1153
cae		Lehar	1154
caf		Southern Carrier	1155
cag		Nivaclé	1156
cah		Cahuarano	1157
caj		Chané	1158
cak		Kaqchikel	1159
cal		Carolinian	1160
cam		Cemuhî	1161
can		Chambri	1162
cao		Chácobo	1163
cap		Chipaya	1164
caq		Car Nicobarese	1165
car		Galibi Carib	1166
cas		Tsimané	1167
cat	ca	Catalan	1168
cav		Cavineña	1169
caw		Callawalla	1170
cax		Chiquitano	1171
cay		Cayuga	1172
caz		Canichana	1173
cbb		Cabiyarí	1174
cbc		Carapana	1175
cbd		Carijona	1176
cbe		Chipiajes	1177
cbg		Chimila	1178
cbh		Cagua	1179
cbi		Chachi	1180
cbj		Ede Cabe	1181
cbk		Chavacano	1182
cbl		Bualkhaw Chin	1183
cbn		Nyahkur	1184
cbo		Izora	1185
cbr		Cashibo-Cacataibo	1186
cbs		Cashinahua	1187
cbt		Chayahuita	1188
cbu		Candoshi-Shapra	1189
cbv		Cacua	1190
cbw		Kinabalian	1191
cby		Carabayo	1192
cca		Cauca	1193
ccc		Chamicuro	1194
ccd		Cafundo Creole	1195
cce		Chopi	1196
ccg		Samba Daka	1197
cch		Atsam	1198
ccj		Kasanga	1199
ccl		Cutchi-Swahili	1200
ccm		Malaccan Creole Malay	1201
cco		Comaltepec Chinantec	1202
ccp		Chakma	1203
ccq		Chaungtha	1204
ccr		Cacaopera	1205
cda		Choni	1206
cde		Chenchu	1207
cdf		Chiru	1208
cdg		Chamari	1209
cdh		Chambeali	1210
cdi		Chodri	1211
cdj		Churahi	1212
cdm		Chepang	1213
cdn		Chaudangsi	1214
cdo		Min Dong Chinese	1215
cdr		Cinda-Regi-Tiyal	1216
cds		Chadian Sign Language	1217
cdy		Chadong	1218
cdz		Koda	1219
cea		Lower Chehalis	1220
ceb		Cebuano	1221
ceg		Chamacoco	1222
cen		Cen	1223
ces	cs	Czech	1224
cet		Centúúm	1225
cfa		Dijim-Bwilim	1226
cfd		Cara	1227
cfg		Como Karim	1228
cfm		Falam Chin	1229
cga		Changriwa	1230
cgc		Kagayanen	1231
cgg		Chiga	1232
cgk		Chocangacakha	1233
cha	ch	Chamorro	1234
chb		Chibcha	1235
chc		Catawba	1236
chd		Highland Oaxaca Chontal	1237
che	ce	Chechen	1238
chf		Tabasco Chontal	1239
chg		Chagatai	1240
chh		Chinook	1241
chj		Ojitlán Chinantec	1242
chk		Chuukese	1243
chl		Cahuilla	1244
chm		Mari (Russia)	1245
chn		Chinook jargon	1246
cho		Choctaw	1247
chp		Chipewyan	1248
chq		Quiotepec Chinantec	1249
chr		Cherokee	1250
cht		Cholón	1251
chu	cu	Church Slavic	1252
chv	cv	Chuvash	1253
chw		Chuwabu	1254
chx		Chantyal	1255
chy		Cheyenne	1256
chz		Ozumacín Chinantec	1257
cia		Cia-Cia	1258
cib		Ci Gbe	1259
cic		Chickasaw	1260
cid		Chimariko	1261
cie		Cineni	1262
cih		Chinali	1263
cik		Chitkuli Kinnauri	1264
cim		Cimbrian	1265
cin		Cinta Larga	1266
cip		Chiapanec	1267
cir		Tiri	1268
ciw		Chippewa	1269
ciy		Chaima	1270
cja		Western Cham	1271
cje		Chru	1272
cjh		Upper Chehalis	1273
cji		Chamalal	1274
cjk		Chokwe	1275
cjm		Eastern Cham	1276
cjn		Chenapian	1277
cjo		Ashéninka Pajonal	1278
cjp		Cabécar	1279
cjs		Shor	1280
cjv		Chuave	1281
cjy		Jinyu Chinese	1282
cka		Khumi Awa Chin	1283
ckb		Central Kurdish	1284
ckh		Chak	1285
ckl		Cibak	1286
cko		Anufo	1287
ckq		Kajakse	1288
ckr		Kairak	1289
cks		Tayo	1290
ckt		Chukot	1291
cku		Koasati	1292
ckv		Kavalan	1293
ckx		Caka	1294
cky		Cakfem-Mushere	1295
ckz		Cakchiquel-Quiché Mixed Language	1296
cla		Ron	1297
clc		Chilcotin	1298
cld		Chaldean Neo-Aramaic	1299
cle		Lealao Chinantec	1300
clh		Chilisso	1301
cli		Chakali	1302
clk		Idu-Mishmi	1303
cll		Chala	1304
clm		Clallam	1305
clo		Lowland Oaxaca Chontal	1306
clu		Caluyanun	1307
clw		Chulym	1308
cly		Eastern Highland Chatino	1309
cma		Maa	1310
cme		Cerma	1311
cmg		Classical Mongolian	1312
cmi		Emberá-Chamí	1313
cml		Campalagian	1314
cmm		Michigamea	1315
cmn		Mandarin Chinese	1316
cmo		Central Mnong	1317
cmr		Mro Chin	1318
cms		Messapic	1319
cmt		Camtho	1320
cna		Changthang	1321
cnb		Chinbon Chin	1322
cnc		Côông	1323
cng		Northern Qiang	1324
cnh		Haka Chin	1325
cni		Asháninka	1326
cnk		Khumi Chin	1327
cnl		Lalana Chinantec	1328
cno		Con	1329
cns		Central Asmat	1330
cnt		Tepetotutla Chinantec	1331
cnu		Chenoua	1332
cnw		Ngawn Chin	1333
cnx		Middle Cornish	1334
coa		Cocos Islands Malay	1335
cob		Chicomuceltec	1336
coc		Cocopa	1337
cod		Cocama-Cocamilla	1338
coe		Koreguaje	1339
cof		Colorado	1340
cog		Chong	1341
coh		Chonyi-Dzihana-Kauma	1342
coj		Cochimi	1343
cok		Santa Teresa Cora	1344
col		Columbia-Wenatchi	1345
com		Comanche	1346
con		Cofán	1347
coo		Comox	1348
cop		Coptic	1349
coq		Coquille	1350
cor	kw	Cornish	1351
cos	co	Corsican	1352
cot		Caquinte	1353
cou		Wamey	1354
cov		Cao Miao	1355
cow		Cowlitz	1356
cox		Nanti	1357
coy		Coyaima	1358
coz		Chochotec	1359
cpa		Palantla Chinantec	1360
cpb		Ucayali-Yurúa Ashéninka	1361
cpc		Ajyíninka Apurucayali	1362
cpg		Cappadocian Greek	1363
cpi		Chinese Pidgin English	1364
cpn		Cherepon	1365
cps		Capiznon	1366
cpu		Pichis Ashéninka	1367
cpx		Pu-Xian Chinese	1368
cpy		South Ucayali Ashéninka	1369
cqd		Chuanqiandian Cluster Miao	1370
cqu		Chilean Quechua	1371
cra		Chara	1372
crb		Island Carib	1373
crc		Lonwolwol	1374
crd		Coeur d'Alene	1375
cre	cr	Cree	1376
crf		Caramanta	1377
crg		Michif	1378
crh		Crimean Tatar	1379
cri		Sãotomense	1380
crj		Southern East Cree	1381
crk		Plains Cree	1382
crl		Northern East Cree	1383
crm		Moose Cree	1384
crn		El Nayar Cora	1385
cro		Crow	1386
crq		Iyo'wujwa Chorote	1387
crr		Carolina Algonquian	1388
crs		Seselwa Creole French	1389
crt		Iyojwa'ja Chorote	1390
crv		Chaura	1391
crw		Chrau	1392
crx		Carrier	1393
cry		Cori	1394
crz		Cruzeño	1395
csa		Chiltepec Chinantec	1396
csb		Kashubian	1397
csc		Catalan Sign Language	1398
csd		Chiangmai Sign Language	1399
cse		Czech Sign Language	1400
csf		Cuba Sign Language	1401
csg		Chilean Sign Language	1402
csh		Asho Chin	1403
csi		Coast Miwok	1404
csk		Jola-Kasa	1405
csl		Chinese Sign Language	1406
csm		Central Sierra Miwok	1407
csn		Colombian Sign Language	1408
cso		Sochiapam Chinantec	1409
csq		Croatia Sign Language	1410
csr		Costa Rican Sign Language	1411
css		Southern Ohlone	1412
cst		Northern Ohlone	1413
csw		Swampy Cree	1414
csy		Siyin Chin	1415
csz		Coos	1416
cta		Tataltepec Chatino	1417
ctc		Chetco	1418
ctd		Tedim Chin	1419
cte		Tepinapa Chinantec	1420
ctg		Chittagonian	1421
ctl		Tlacoatzintepec Chinantec	1422
ctm		Chitimacha	1423
ctn		Chhintange	1424
cto		Emberá-Catío	1425
ctp		Western Highland Chatino	1426
cts		Northern Catanduanes Bicolano	1427
ctt		Wayanad Chetti	1428
ctu		Chol	1429
ctz		Zacatepec Chatino	1430
cua		Cua	1431
cub		Cubeo	1432
cuc		Usila Chinantec	1433
cug		Cung	1434
cuh		Chuka	1435
cui		Cuiba	1436
cuj		Mashco Piro	1437
cuk		San Blas Kuna	1438
cul		Culina	1439
cum		Cumeral	1440
cuo		Cumanagoto	1441
cup		Cupeño	1442
cuq		Cun	1443
cur		Chhulung	1444
cut		Teutila Cuicatec	1445
cuu		Tai Ya	1446
cuv		Cuvok	1447
cuw		Chukwa	1448
cux		Tepeuxila Cuicatec	1449
cvg		Chug	1450
cvn		Valle Nacional Chinantec	1451
cwa		Kabwa	1452
cwb		Maindo	1453
cwd		Woods Cree	1454
cwe		Kwere	1455
cwg		Chewong	1456
cwt		Kuwaataay	1457
cya		Nopala Chatino	1458
cyb		Cayubaba	1459
cym	cy	Welsh	1460
cyo		Cuyonon	1461
czh		Huizhou Chinese	1462
czk		Knaanic	1463
czn		Zenzontepec Chatino	1464
czo		Min Zhong Chinese	1465
czt		Zotung Chin	1466
daa		Dangaléat	1467
dac		Dambi	1468
dad		Marik	1469
dae		Duupa	1470
daf		Dan	1471
dag		Dagbani	1472
dah		Gwahatike	1473
dai		Day	1474
daj		Dar Fur Daju	1475
dak		Dakota	1476
dal		Dahalo	1477
dam		Damakawa	1478
dan	da	Danish	1479
dao		Daai Chin	1480
dap		Nisi (India)	1481
daq		Dandami Maria	1482
dar		Dargwa	1483
das		Daho-Doo	1484
dau		Dar Sila Daju	1485
dav		Taita	1486
daw		Davawenyo	1487
dax		Dayi	1488
daz		Dao	1489
dba		Bangi Me	1490
dbb		Deno	1491
dbd		Dadiya	1492
dbe		Dabe	1493
dbf		Edopi	1494
dbg		Dogul Dom Dogon	1495
dbi		Doka	1496
dbj		Ida'an	1497
dbl		Dyirbal	1498
dbm		Duguri	1499
dbn		Duriankere	1500
dbo		Dulbu	1501
dbp		Duwai	1502
dbq		Daba	1503
dbr		Dabarre	1504
dbu		Bondum Dom Dogon	1505
dbv		Dungu	1506
dby		Dibiyaso	1507
dcc		Deccan	1508
dcr		Negerhollands	1509
ddd		Dongotono	1510
dde		Doondo	1511
ddg		Fataluku	1512
ddi		West Goodenough	1513
ddj		Jaru	1514
ddn		Dendi (Benin)	1515
ddo		Dido	1516
dds		Donno So Dogon	1517
ddw		Dawera-Daweloor	1518
dec		Dagik	1519
ded		Dedua	1520
dee		Dewoin	1521
def		Dezfuli	1522
deg		Degema	1523
deh		Dehwari	1524
dei		Demisa	1525
dek		Dek	1526
del		Delaware	1527
dem		Dem	1528
den		Slave (Athapascan)	1529
dep		Pidgin Delaware	1530
deq		Dendi (Central African Republic)	1531
der		Deori	1532
des		Desano	1533
deu	de	German	1534
dev		Domung	1535
dez		Dengese	1536
dga		Southern Dagaare	1537
dgb		Bunoge Dogon	1538
dgc		Casiguran Dumagat Agta	1539
dgd		Dagaari Dioula	1540
dge		Degenan	1541
dgg		Doga	1542
dgh		Dghwede	1543
dgi		Northern Dagara	1544
dgk		Dagba	1545
dgn		Dagoman	1546
dgo		Dogri (individual language)	1547
dgr		Dogrib	1548
dgs		Dogoso	1549
dgu		Degaru	1550
dgx		Doghoro	1551
dgz		Daga	1552
dha		Dhanwar (India)	1553
dhd		Dhundari	1554
dhg		Dhangu	1555
dhi		Dhimal	1556
dhl		Dhalandji	1557
dhm		Zemba	1558
dhn		Dhanki	1559
dho		Dhodia	1560
dhr		Dhargari	1561
dhs		Dhaiso	1562
dhu		Dhurga	1563
dhv		Dehu	1564
dhw		Dhanwar (Nepal)	1565
dia		Dia	1566
dib		South Central Dinka	1567
dic		Lakota Dida	1568
did		Didinga	1569
dif		Dieri	1570
dig		Digo	1571
dih		Kumiai	1572
dii		Dimbong	1573
dij		Dai	1574
dik		Southwestern Dinka	1575
dil		Dilling	1576
dim		Dime	1577
din		Dinka	1578
dio		Dibo	1579
dip		Northeastern Dinka	1580
diq		Dimli (individual language)	1581
dir		Dirim	1582
dis		Dimasa	1583
dit		Dirari	1584
diu		Diriku	1585
div	dv	Dhivehi	1586
diw		Northwestern Dinka	1587
dix		Dixon Reef	1588
diy		Diuwe	1589
diz		Ding	1590
djb		Djinba	1591
djc		Dar Daju Daju	1592
djd		Djamindjung	1593
dje		Zarma	1594
djf		Djangun	1595
dji		Djinang	1596
djj		Djeebbana	1597
djk		Eastern Maroon Creole	1598
djl		Djiwarli	1599
djm		Jamsay Dogon	1600
djn		Djauan	1601
djo		Jangkang	1602
djr		Djambarrpuyngu	1603
dju		Kapriman	1604
djw		Djawi	1605
dka		Dakpakha	1606
dkk		Dakka	1607
dkl		Kolum So Dogon	1608
dkr		Kuijau	1609
dks		Southeastern Dinka	1610
dkx		Mazagway	1611
dlg		Dolgan	1612
dlm		Dalmatian	1613
dln		Darlong	1614
dma		Duma	1615
dmc		Dimir	1616
dme		Dugwor	1617
dmg		Upper Kinabatangan	1618
dmk		Domaaki	1619
dml		Dameli	1620
dmm		Dama	1621
dmo		Kemezung	1622
dmr		East Damar	1623
dms		Dampelas	1624
dmu		Dubu	1625
dmv		Dumpas	1626
dmx		Dema	1627
dmy		Demta	1628
dna		Upper Grand Valley Dani	1629
dnd		Daonda	1630
dne		Ndendeule	1631
dng		Dungan	1632
dni		Lower Grand Valley Dani	1633
dnk		Dengka	1634
dnn		Dzùùngoo	1635
dnr		Danaru	1636
dnt		Mid Grand Valley Dani	1637
dnu		Danau	1638
dnw		Western Dani	1639
dny		Dení	1640
doa		Dom	1641
dob		Dobu	1642
doc		Northern Dong	1643
doe		Doe	1644
dof		Domu	1645
doh		Dong	1646
doi		Dogri (macrolanguage)	1647
dok		Dondo	1648
dol		Doso	1649
don		Toura (Papua New Guinea)	1650
doo		Dongo	1651
dop		Lukpa	1652
doq		Dominican Sign Language	1653
dor		Dori'o	1654
dos		Dogosé	1655
dot		Dass	1656
dov		Dombe	1657
dow		Doyayo	1658
dox		Bussa	1659
doy		Dompo	1660
doz		Dorze	1661
dpp		Papar	1662
drb		Dair	1663
drd		Darmiya	1664
dre		Dolpo	1665
drg		Rungus	1666
dri		C'lela	1667
drl		Darling	1668
drn		West Damar	1669
dro		Daro-Matu Melanau	1670
drq		Dura	1671
drr		Dororo	1672
drs		Gedeo	1673
drt		Drents	1674
dru		Rukai	1675
dry		Darai	1676
dsb		Lower Sorbian	1677
dse		Dutch Sign Language	1678
dsh		Daasanach	1679
dsi		Disa	1680
dsl		Danish Sign Language	1681
dsn		Dusner	1682
dso		Desiya	1683
dsq		Tadaksahak	1684
dta		Daur	1685
dtb		Labuk-Kinabatangan Kadazan	1686
dti		Ana Tinga Dogon	1687
dtk		Tene Kan Dogon	1688
dtm		Tomo Kan Dogon	1689
dtp		Central Dusun	1690
dtr		Lotud	1691
dts		Toro So Dogon	1692
dtt		Toro Tegu Dogon	1693
dtu		Tebul Ure Dogon	1694
dua		Duala	1695
dub		Dubli	1696
duc		Duna	1697
dud		Hun-Saare	1698
due		Umiray Dumaget Agta	1699
duf		Dumbea	1700
dug		Duruma	1701
duh		Dungra Bhil	1702
dui		Dumun	1703
duj		Dhuwal	1704
duk		Duduela	1705
dul		Alabat Island Agta	1706
dum		Middle Dutch (ca. 1050-1350)	1707
dun		Dusun Deyah	1708
duo		Dupaninan Agta	1709
dup		Duano	1710
duq		Dusun Malang	1711
dur		Dii	1712
dus		Dumi	1713
duu		Drung	1714
duv		Duvle	1715
duw		Dusun Witu	1716
dux		Duungooma	1717
duy		Dicamay Agta	1718
duz		Duli	1719
dva		Duau	1720
dwa		Diri	1721
dwl		Walo Kumbe Dogon	1722
dwr		Dawro	1723
dws		Dutton World Speedwords	1724
dww		Dawawa	1725
dya		Dyan	1726
dyb		Dyaberdyaber	1727
dyd		Dyugun	1728
dyg		Villa Viciosa Agta	1729
dyi		Djimini Senoufo	1730
dym		Yanda Dom Dogon	1731
dyn		Dyangadi	1732
dyo		Jola-Fonyi	1733
dyu		Dyula	1734
dyy		Dyaabugay	1735
dza		Tunzu	1736
dzd		Daza	1737
dzg		Dazaga	1738
dzl		Dzalakha	1739
dzn		Dzando	1740
dzo	dz	Dzongkha	1741
ebg		Ebughu	1742
ebk		Eastern Bontok	1743
ebo		Teke-Ebo	1744
ebr		Ebrié	1745
ebu		Embu	1746
ecr		Eteocretan	1747
ecs		Ecuadorian Sign Language	1748
ecy		Eteocypriot	1749
eee		E	1750
efa		Efai	1751
efe		Efe	1752
efi		Efik	1753
ega		Ega	1754
egl		Emilian	1755
ego		Eggon	1756
egy		Egyptian (Ancient)	1757
ehu		Ehueun	1758
eip		Eipomek	1759
eit		Eitiep	1760
eiv		Askopan	1761
eja		Ejamat	1762
eka		Ekajuk	1763
eke		Ekit	1764
ekg		Ekari	1765
eki		Eki	1766
ekk		Standard Estonian	1767
ekl		Kol	1768
ekm		Elip	1769
eko		Koti	1770
ekp		Ekpeye	1771
ekr		Yace	1772
eky		Eastern Kayah	1773
ele		Elepi	1774
elh		El Hugeirat	1775
eli		Nding	1776
elk		Elkei	1777
ell	el	Modern Greek (1453-)	1778
elm		Eleme	1779
elo		El Molo	1780
elp		Elpaputih	1781
elu		Elu	1782
elx		Elamite	1783
ema		Emai-Iuleha-Ora	1784
emb		Embaloh	1785
eme		Emerillon	1786
emg		Eastern Meohang	1787
emi		Mussau-Emira	1788
emk		Eastern Maninkakan	1789
emm		Mamulique	1790
emn		Eman	1791
emo		Emok	1792
emp		Northern Emberá	1793
ems		Pacific Gulf Yupik	1794
emu		Eastern Muria	1795
emw		Emplawas	1796
emx		Erromintxela	1797
emy		Epigraphic Mayan	1798
ena		Apali	1799
enb		Markweeta	1800
enc		En	1801
end		Ende	1802
enf		Forest Enets	1803
eng	en	English	1804
enh		Tundra Enets	1805
enm		Middle English (1100-1500)	1806
enn		Engenni	1807
eno		Enggano	1808
enq		Enga	1809
enr		Emumu	1810
enu		Enu	1811
env		Enwan (Edu State)	1812
enw		Enwan (Akwa Ibom State)	1813
eot		Beti (Côte d'Ivoire)	1814
epi		Epie	1815
epo	eo	Esperanto	1816
era		Eravallan	1817
erg		Sie	1818
erh		Eruwa	1819
eri		Ogea	1820
erk		South Efate	1821
ero		Horpa	1822
err		Erre	1823
ers		Ersu	1824
ert		Eritai	1825
erw		Erokwanas	1826
ese		Ese Ejja	1827
esh		Eshtehardi	1828
esi		North Alaskan Inupiatun	1829
esk		Northwest Alaska Inupiatun	1830
esl		Egypt Sign Language	1831
esm		Esuma	1832
esn		Salvadoran Sign Language	1833
eso		Estonian Sign Language	1834
esq		Esselen	1835
ess		Central Siberian Yupik	1836
est	et	Estonian	1837
esu		Central Yupik	1838
etb		Etebi	1839
etc		Etchemin	1840
eth		Ethiopian Sign Language	1841
etn		Eton (Vanuatu)	1842
eto		Eton (Cameroon)	1843
etr		Edolo	1844
ets		Yekhee	1845
ett		Etruscan	1846
etu		Ejagham	1847
etx		Eten	1848
etz		Semimi	1849
eus	eu	Basque	1850
eve		Even	1851
evh		Uvbie	1852
evn		Evenki	1853
ewe	ee	Ewe	1854
ewo		Ewondo	1855
ext		Extremaduran	1856
eya		Eyak	1857
eyo		Keiyo	1858
eze		Uzekwe	1859
faa		Fasu	1860
fab		Fa D'ambu	1861
fad		Wagi	1862
faf		Fagani	1863
fag		Finongan	1864
fah		Baissa Fali	1865
fai		Faiwol	1866
faj		Faita	1867
fak		Fang (Cameroon)	1868
fal		South Fali	1869
fam		Fam	1870
fan		Fang (Equatorial Guinea)	1871
fao	fo	Faroese	1872
fap		Palor	1873
far		Fataleka	1874
fas	fa	Persian	1875
fat		Fanti	1876
fau		Fayu	1877
fax		Fala	1878
fay		Southwestern Fars	1879
faz		Northwestern Fars	1880
fbl		West Albay Bikol	1881
fcs		Quebec Sign Language	1882
fer		Feroge	1883
ffi		Foia Foia	1884
ffm		Maasina Fulfulde	1885
fgr		Fongoro	1886
fia		Nobiin	1887
fie		Fyer	1888
fij	fj	Fijian	1889
fil		Filipino	1890
fin	fi	Finnish	1891
fip		Fipa	1892
fir		Firan	1893
fit		Tornedalen Finnish	1894
fiw		Fiwaga	1895
fkv		Kven Finnish	1896
fla		Kalispel-Pend d'Oreille	1897
flh		Foau	1898
fli		Fali	1899
fll		North Fali	1900
fln		Flinders Island	1901
flr		Fuliiru	1902
fly		Tsotsitaal	1903
fmp		Fe'fe'	1904
fmu		Far Western Muria	1905
fng		Fanagalo	1906
fni		Fania	1907
fod		Foodo	1908
foi		Foi	1909
fom		Foma	1910
fon		Fon	1911
for		Fore	1912
fos		Siraya	1913
fpe		Fernando Po Creole English	1914
fqs		Fas	1915
fra	fr	French	1916
frc		Cajun French	1917
frd		Fordata	1918
frk		Frankish	1919
frm		Middle French (ca. 1400-1600)	1920
frp		Arpitan	1921
frq		Forak	1922
frr		Northern Frisian	1923
frs		Eastern Frisian	1924
frt		Fortsenal	1925
fry	fy	Western Frisian	1926
fse		Finnish Sign Language	1927
fsl		French Sign Language	1928
fss		Finland-Swedish Sign Language	1929
fub		Adamawa Fulfulde	1930
fuc		Pulaar	1931
fud		East Futuna	1932
fue		Borgu Fulfulde	1933
fuf		Pular	1934
fuh		Western Niger Fulfulde	1935
fui		Bagirmi Fulfulde	1936
fuj		Ko	1937
ful	ff	Fulah	1938
fum		Fum	1939
fun		Fulniô	1940
fuq		Central-Eastern Niger Fulfulde	1941
fur		Friulian	1942
fut		Futuna-Aniwa	1943
fuu		Furu	1944
fuv		Nigerian Fulfulde	1945
fuy		Fuyug	1946
fvr		Fur	1947
fwa		Fwâi	1948
fwe		Fwe	1949
gaa		Ga	1950
gab		Gabri	1951
gac		Mixed Great Andamanese	1952
gad		Gaddang	1953
gae		Guarequena	1954
gaf		Gende	1955
gag		Gagauz	1956
gah		Alekano	1957
gai		Borei	1958
gaj		Gadsup	1959
gak		Gamkonora	1960
gal		Galoli	1961
gam		Kandawo	1962
gan		Gan Chinese	1963
gao		Gants	1964
gap		Gal	1965
gaq		Gata'	1966
gar		Galeya	1967
gas		Adiwasi Garasia	1968
gat		Kenati	1969
gau		Mudhili Gadaba	1970
gaw		Nobonob	1971
gax		Borana-Arsi-Guji Oromo	1972
gay		Gayo	1973
gaz		West Central Oromo	1974
gba		Gbaya (Central African Republic)	1975
gbb		Kaytetye	1976
gbc		Garawa	1977
gbd		Karadjeri	1978
gbe		Niksek	1979
gbf		Gaikundi	1980
gbg		Gbanziri	1981
gbh		Defi Gbe	1982
gbi		Galela	1983
gbj		Bodo Gadaba	1984
gbk		Gaddi	1985
gbl		Gamit	1986
gbm		Garhwali	1987
gbn		Mo'da	1988
gbo		Northern Grebo	1989
gbp		Gbaya-Bossangoa	1990
gbq		Gbaya-Bozoum	1991
gbr		Gbagyi	1992
gbs		Gbesi Gbe	1993
gbu		Gagadu	1994
gbv		Gbanu	1995
gbx		Eastern Xwla Gbe	1996
gby		Gbari	1997
gbz		Zoroastrian Dari	1998
gcc		Mali	1999
gcd		Ganggalida	2000
gce		Galice	2001
gcf		Guadeloupean Creole French	2002
gcl		Grenadian Creole English	2003
gcn		Gaina	2004
gcr		Guianese Creole French	2005
gct		Colonia Tovar German	2006
gda		Gade Lohar	2007
gdb		Pottangi Ollar Gadaba	2008
gdc		Gugu Badhun	2009
gdd		Gedaged	2010
gde		Gude	2011
gdf		Guduf-Gava	2012
gdg		Ga'dang	2013
gdh		Gadjerawang	2014
gdi		Gundi	2015
gdj		Gurdjar	2016
gdk		Gadang	2017
gdl		Dirasha	2018
gdm		Laal	2019
gdn		Umanakaina	2020
gdo		Ghodoberi	2021
gdq		Mehri	2022
gdr		Wipi	2023
gdu		Gudu	2024
gdx		Godwari	2025
gea		Geruma	2026
geb		Kire	2027
gec		Gboloo Grebo	2028
ged		Gade	2029
geg		Gengle	2030
geh		Hutterite German	2031
gei		Gebe	2032
gej		Gen	2033
gek		Yiwom	2034
gel		Kag-Fer-Jiir-Koor-Ror-Us-Zuksun	2035
geq		Geme	2036
ges		Geser-Gorom	2037
gew		Gera	2038
gex		Garre	2039
gey		Enya	2040
gez		Geez	2041
gfk		Patpatar	2042
gft		Gafat	2043
gga		Gao	2044
ggb		Gbii	2045
ggd		Gugadj	2046
gge		Guragone	2047
ggg		Gurgula	2048
ggk		Kungarakany	2049
ggl		Ganglau	2050
ggn		Eastern Gurung	2051
ggo		Southern Gondi	2052
ggr		Aghu Tharnggalu	2053
ggt		Gitua	2054
ggu		Gagu	2055
ggw		Gogodala	2056
gha		Ghadamès	2057
ghc		Hiberno-Scottish Gaelic	2058
ghe		Southern Ghale	2059
ghh		Northern Ghale	2060
ghk		Geko Karen	2061
ghl		Ghulfan	2062
ghn		Ghanongga	2063
gho		Ghomara	2064
ghr		Ghera	2065
ghs		Guhu-Samane	2066
ght		Kutang Ghale	2067
gia		Kitja	2068
gib		Gibanawa	2069
gic		Gail	2070
gid		Gidar	2071
gig		Goaria	2072
gil		Gilbertese	2073
gim		Gimi (Eastern Highlands)	2074
gin		Hinukh	2075
gio		Gelao	2076
gip		Gimi (West New Britain)	2077
giq		Green Gelao	2078
gir		Red Gelao	2079
gis		North Giziga	2080
git		Gitxsan	2081
giw		White Gelao	2082
gix		Gilima	2083
giy		Giyug	2084
giz		South Giziga	2085
gji		Geji	2086
gjk		Kachi Koli	2087
gjn		Gonja	2088
gju		Gujari	2089
gka		Guya	2090
gke		Ndai	2091
gkn		Gokana	2092
gkp		Guinea Kpelle	2093
gla	gd	Scottish Gaelic	2094
glc		Bon Gula	2095
gld		Nanai	2096
gle	ga	Irish	2097
glg	gl	Galician	2098
glh		Northwest Pashayi	2099
gli		Guliguli	2100
glj		Gula Iro	2101
glk		Gilaki	2102
glo		Galambu	2103
glr		Glaro-Twabo	2104
glu		Gula (Chad)	2105
glv	gv	Manx	2106
glw		Glavda	2107
gly		Gule	2108
gma		Gambera	2109
gmb		Gula'alaa	2110
gmd		Mághdì	2111
gmh		Middle High German (ca. 1050-1500)	2112
gml		Middle Low German	2113
gmm		Gbaya-Mbodomo	2114
gmn		Gimnime	2115
gmu		Gumalu	2116
gmv		Gamo	2117
gmx		Magoma	2118
gmy		Mycenaean Greek	2119
gna		Kaansa	2120
gnb		Gangte	2121
gnc		Guanche	2122
gnd		Zulgo-Gemzek	2123
gne		Ganang	2124
gng		Ngangam	2125
gnh		Lere	2126
gni		Gooniyandi	2127
gnk		//Gana	2128
gnl		Gangulu	2129
gnm		Ginuman	2130
gnn		Gumatj	2131
gno		Northern Gondi	2132
gnq		Gana	2133
gnr		Gureng Gureng	2134
gnt		Guntai	2135
gnu		Gnau	2136
gnw		Western Bolivian Guaraní	2137
gnz		Ganzi	2138
goa		Guro	2139
gob		Playero	2140
goc		Gorakor	2141
god		Godié	2142
goe		Gongduk	2143
gof		Gofa	2144
gog		Gogo	2145
goh		Old High German (ca. 750-1050)	2146
goi		Gobasi	2147
goj		Gowlan	2148
gok		Gowli	2149
gol		Gola	2150
gom		Goan Konkani	2151
gon		Gondi	2152
goo		Gone Dau	2153
gop		Yeretuar	2154
goq		Gorap	2155
gor		Gorontalo	2156
gos		Gronings	2157
got		Gothic	2158
gou		Gavar	2159
gow		Gorowa	2160
gox		Gobu	2161
goy		Goundo	2162
goz		Gozarkhani	2163
gpa		Gupa-Abawa	2164
gpn		Taiap	2165
gqa		Ga'anda	2166
gqi		Guiqiong	2167
gqn		Guana (Brazil)	2168
gqr		Gor	2169
gra		Rajput Garasia	2170
grb		Grebo	2171
grd		Guruntum-Mbaaru	2172
grg		Madi	2173
grh		Gbiri-Niragu	2174
gri		Ghari	2175
grj		Southern Grebo	2176
grm		Kota Marudu Talantang	2177
grn	gn	Guarani	2178
gro		Groma	2179
grq		Gorovu	2180
grr		Taznatit	2181
grs		Gresi	2182
grt		Garo	2183
gru		Kistane	2184
grv		Central Grebo	2185
grw		Gweda	2186
grx		Guriaso	2187
gry		Barclayville Grebo	2188
grz		Guramalum	2189
gse		Ghanaian Sign Language	2190
gsg		German Sign Language	2191
gsl		Gusilay	2192
gsm		Guatemalan Sign Language	2193
gsn		Gusan	2194
gso		Southwest Gbaya	2195
gsp		Wasembo	2196
gss		Greek Sign Language	2197
gsw		Swiss German	2198
gta		Guató	2199
gti		Gbati-ri	2200
gua		Shiki	2201
gub		Guajajára	2202
guc		Wayuu	2203
gud		Yocoboué Dida	2204
gue		Gurinji	2205
guf		Gupapuyngu	2206
gug		Paraguayan Guaraní	2207
guh		Guahibo	2208
gui		Eastern Bolivian Guaraní	2209
guj	gu	Gujarati	2210
guk		Gumuz	2211
gul		Sea Island Creole English	2212
gum		Guambiano	2213
gun		Mbyá Guaraní	2214
guo		Guayabero	2215
gup		Gunwinggu	2216
guq		Aché	2217
gur		Farefare	2218
gus		Guinean Sign Language	2219
gut		Maléku Jaíka	2220
guu		Yanomamö	2221
guv		Gey	2222
guw		Gun	2223
gux		Gourmanchéma	2224
guz		Gusii	2225
gva		Guana (Paraguay)	2226
gvc		Guanano	2227
gve		Duwet	2228
gvf		Golin	2229
gvj		Guajá	2230
gvl		Gulay	2231
gvm		Gurmana	2232
gvn		Kuku-Yalanji	2233
gvo		Gavião Do Jiparaná	2234
gvp		Pará Gavião	2235
gvr		Western Gurung	2236
gvs		Gumawana	2237
gvy		Guyani	2238
gwa		Mbato	2239
gwb		Gwa	2240
gwc		Kalami	2241
gwd		Gawwada	2242
gwe		Gweno	2243
gwf		Gowro	2244
gwg		Moo	2245
gwi		Gwichʼin	2246
gwj		/Gwi	2247
gwn		Gwandara	2248
gwr		Gwere	2249
gwt		Gawar-Bati	2250
gwu		Guwamu	2251
gww		Kwini	2252
gwx		Gua	2253
gxx		Wè Southern	2254
gya		Northwest Gbaya	2255
gyb		Garus	2256
gyd		Kayardild	2257
gye		Gyem	2258
gyf		Gungabula	2259
gyg		Gbayi	2260
gyi		Gyele	2261
gyl		Gayil	2262
gym		Ngäbere	2263
gyn		Guyanese Creole English	2264
gyr		Guarayu	2265
gyy		Gunya	2266
gza		Ganza	2267
gzi		Gazi	2268
gzn		Gane	2269
haa		Han	2270
hab		Hanoi Sign Language	2271
hac		Gurani	2272
had		Hatam	2273
hae		Eastern Oromo	2274
haf		Haiphong Sign Language	2275
hag		Hanga	2276
hah		Hahon	2277
hai		Haida	2278
haj		Hajong	2279
hak		Hakka Chinese	2280
hal		Halang	2281
ham		Hewa	2282
han		Hangaza	2283
hao		Hakö	2284
hap		Hupla	2285
haq		Ha	2286
har		Harari	2287
has		Haisla	2288
hat	ht	Haitian	2289
hau	ha	Hausa	2290
hav		Havu	2291
haw		Hawaiian	2292
hax		Southern Haida	2293
hay		Haya	2294
haz		Hazaragi	2295
hba		Hamba	2296
hbb		Huba	2297
hbn		Heiban	2298
hbo		Ancient Hebrew	2299
hbu		Habu	2300
hca		Andaman Creole Hindi	2301
hch		Huichol	2302
hdn		Northern Haida	2303
hds		Honduras Sign Language	2304
hdy		Hadiyya	2305
hea		Northern Qiandong Miao	2306
heb	he	Hebrew	2307
hed		Herdé	2308
heg		Helong	2309
heh		Hehe	2310
hei		Heiltsuk	2311
hem		Hemba	2312
her	hz	Herero	2313
hgm		Hai//om	2314
hgw		Haigwai	2315
hhi		Hoia Hoia	2316
hhr		Kerak	2317
hhy		Hoyahoya	2318
hia		Lamang	2319
hib		Hibito	2320
hid		Hidatsa	2321
hif		Fiji Hindi	2322
hig		Kamwe	2323
hih		Pamosu	2324
hii		Hinduri	2325
hij		Hijuk	2326
hik		Seit-Kaitetu	2327
hil		Hiligaynon	2328
hin	hi	Hindi	2329
hio		Tsoa	2330
hir		Himarimã	2331
hit		Hittite	2332
hiw		Hiw	2333
hix		Hixkaryána	2334
hji		Haji	2335
hka		Kahe	2336
hke		Hunde	2337
hkk		Hunjara-Kaina Ke	2338
hks		Hong Kong Sign Language	2339
hla		Halia	2340
hlb		Halbi	2341
hld		Halang Doan	2342
hle		Hlersu	2343
hlt		Nga La	2344
hlu		Hieroglyphic Luwian	2345
hma		Southern Mashan Hmong	2346
hmb		Humburi Senni Songhay	2347
hmc		Central Huishui Hmong	2348
hmd		Large Flowery Miao	2349
hme		Eastern Huishui Hmong	2350
hmf		Hmong Don	2351
hmg		Southwestern Guiyang Hmong	2352
hmh		Southwestern Huishui Hmong	2353
hmi		Northern Huishui Hmong	2354
hmj		Ge	2355
hmk		Maek	2356
hml		Luopohe Hmong	2357
hmm		Central Mashan Hmong	2358
hmn		Hmong	2359
hmo	ho	Hiri Motu	2360
hmp		Northern Mashan Hmong	2361
hmq		Eastern Qiandong Miao	2362
hmr		Hmar	2363
hms		Southern Qiandong Miao	2364
hmt		Hamtai	2365
hmu		Hamap	2366
hmv		Hmong Dô	2367
hmw		Western Mashan Hmong	2368
hmy		Southern Guiyang Hmong	2369
hmz		Hmong Shua	2370
hna		Mina (Cameroon)	2371
hnd		Southern Hindko	2372
hne		Chhattisgarhi	2373
hnh		//Ani	2374
hni		Hani	2375
hnj		Hmong Njua	2376
hnn		Hanunoo	2377
hno		Northern Hindko	2378
hns		Caribbean Hindustani	2379
hnu		Hung	2380
hoa		Hoava	2381
hob		Mari (Madang Province)	2382
hoc		Ho	2383
hod		Holma	2384
hoe		Horom	2385
hoh		Hobyót	2386
hoi		Holikachuk	2387
hoj		Hadothi	2388
hol		Holu	2389
hom		Homa	2390
hoo		Holoholo	2391
hop		Hopi	2392
hor		Horo	2393
hos		Ho Chi Minh City Sign Language	2394
hot		Hote	2395
hov		Hovongan	2396
how		Honi	2397
hoy		Holiya	2398
hoz		Hozo	2399
hpo		Hpon	2400
hps		Hawai'i Pidgin Sign Language	2401
hra		Hrangkhol	2402
hre		Hre	2403
hrk		Haruku	2404
hrm		Horned Miao	2405
hro		Haroi	2406
hrr		Horuru	2407
hrt		Hértevin	2408
hru		Hruso	2409
hrv	hr	Croatian	2410
hrx		Hunsrik	2411
hrz		Harzani	2412
hsb		Upper Sorbian	2413
hsh		Hungarian Sign Language	2414
hsl		Hausa Sign Language	2415
hsn		Xiang Chinese	2416
hss		Harsusi	2417
hti		Hoti	2418
hto		Minica Huitoto	2419
hts		Hadza	2420
htu		Hitu	2421
htx		Middle Hittite	2422
hub		Huambisa	2423
huc		=/Hua	2424
hud		Huaulu	2425
hue		San Francisco Del Mar Huave	2426
huf		Humene	2427
hug		Huachipaeri	2428
huh		Huilliche	2429
hui		Huli	2430
huj		Northern Guiyang Hmong	2431
huk		Hulung	2432
hul		Hula	2433
hum		Hungana	2434
hun	hu	Hungarian	2435
huo		Hu	2436
hup		Hupa	2437
huq		Tsat	2438
hur		Halkomelem	2439
hus		Huastec	2440
hut		Humla	2441
huu		Murui Huitoto	2442
huv		San Mateo Del Mar Huave	2443
huw		Hukumina	2444
hux		Nüpode Huitoto	2445
huy		Hulaulá	2446
huz		Hunzib	2447
hvc		Haitian Vodoun Culture Language	2448
hve		San Dionisio Del Mar Huave	2449
hvk		Haveke	2450
hvn		Sabu	2451
hvv		Santa María Del Mar Huave	2452
hwa		Wané	2453
hwc		Hawai'i Creole English	2454
hwo		Hwana	2455
hya		Hya	2456
hye	hy	Armenian	2457
iai		Iaai	2458
ian		Iatmul	2459
iap		Iapama	2460
iar		Purari	2461
iba		Iban	2462
ibb		Ibibio	2463
ibd		Iwaidja	2464
ibe		Akpes	2465
ibg		Ibanag	2466
ibi		Ibilo	2467
ibl		Ibaloi	2468
ibm		Agoi	2469
ibn		Ibino	2470
ibo	ig	Igbo	2471
ibr		Ibuoro	2472
ibu		Ibu	2473
iby		Ibani	2474
ica		Ede Ica	2475
ich		Etkywan	2476
icl		Icelandic Sign Language	2477
icr		Islander Creole English	2478
ida		Idakho-Isukha-Tiriki	2479
idb		Indo-Portuguese	2480
idc		Idon	2481
idd		Ede Idaca	2482
ide		Idere	2483
idi		Idi	2484
ido	io	Ido	2485
idr		Indri	2486
ids		Idesa	2487
idt		Idaté	2488
idu		Idoma	2489
ifa		Amganad Ifugao	2490
ifb		Batad Ifugao	2491
ife		Ifè	2492
iff		Ifo	2493
ifk		Tuwali Ifugao	2494
ifm		Teke-Fuumu	2495
ifu		Mayoyao Ifugao	2496
ify		Keley-I Kallahan	2497
igb		Ebira	2498
ige		Igede	2499
igg		Igana	2500
igl		Igala	2501
igm		Kanggape	2502
ign		Ignaciano	2503
igo		Isebe	2504
igs		Interglossa	2505
igw		Igwe	2506
ihb		Iha Based Pidgin	2507
ihi		Ihievbe	2508
ihp		Iha	2509
iii	ii	Sichuan Yi	2510
ijc		Izon	2511
ije		Biseni	2512
ijj		Ede Ije	2513
ijn		Kalabari	2514
ijs		Southeast Ijo	2515
ike		Eastern Canadian Inuktitut	2516
iki		Iko	2517
ikk		Ika	2518
ikl		Ikulu	2519
iko		Olulumo-Ikom	2520
ikp		Ikpeshi	2521
ikt		Western Canadian Inuktitut	2522
iku	iu	Inuktitut	2523
ikv		Iku-Gora-Ankwa	2524
ikw		Ikwere	2525
ikx		Ik	2526
ikz		Ikizu	2527
ila		Ile Ape	2528
ilb		Ila	2529
ile	ie	Interlingue	2530
ilg		Garig-Ilgar	2531
ili		Ili Turki	2532
ilk		Ilongot	2533
ill		Iranun	2534
ilo		Iloko	2535
ils		International Sign	2536
ilu		Ili'uun	2537
ilv		Ilue	2538
ilw		Talur	2539
ima		Mala Malasar	2540
ime		Imeraguen	2541
imi		Anamgura	2542
iml		Miluk	2543
imn		Imonda	2544
imo		Imbongu	2545
imr		Imroing	2546
ims		Marsian	2547
imy		Milyan	2548
ina	ia	Interlingua (International Auxiliary Language Association)	2549
inb		Inga	2550
ind	id	Indonesian	2551
ing		Degexit'an	2552
inh		Ingush	2553
inj		Jungle Inga	2554
inl		Indonesian Sign Language	2555
inm		Minaean	2556
inn		Isinai	2557
ino		Inoke-Yate	2558
inp		Iñapari	2559
ins		Indian Sign Language	2560
int		Intha	2561
inz		Ineseño	2562
ior		Inor	2563
iou		Tuma-Irumu	2564
iow		Iowa-Oto	2565
ipi		Ipili	2566
ipk	ik	Inupiaq	2567
ipo		Ipiko	2568
iqu		Iquito	2569
ire		Iresim	2570
irh		Irarutu	2571
iri		Irigwe	2572
irk		Iraqw	2573
irn		Irántxe	2574
irr		Ir	2575
iru		Irula	2576
irx		Kamberau	2577
iry		Iraya	2578
isa		Isabi	2579
isc		Isconahua	2580
isd		Isnag	2581
ise		Italian Sign Language	2582
isg		Irish Sign Language	2583
ish		Esan	2584
isi		Nkem-Nkum	2585
isk		Ishkashimi	2586
isl	is	Icelandic	2587
ism		Masimasi	2588
isn		Isanzu	2589
iso		Isoko	2590
isr		Israeli Sign Language	2591
ist		Istriot	2592
isu		Isu (Menchum Division)	2593
ita	it	Italian	2594
itb		Binongan Itneg	2595
ite		Itene	2596
iti		Inlaod Itneg	2597
itk		Judeo-Italian	2598
itl		Itelmen	2599
itm		Itu Mbon Uzo	2600
ito		Itonama	2601
itr		Iteri	2602
its		Isekiri	2603
itt		Maeng Itneg	2604
itv		Itawit	2605
itw		Ito	2606
itx		Itik	2607
ity		Moyadan Itneg	2608
itz		Itzá	2609
ium		Iu Mien	2610
ivb		Ibatan	2611
ivv		Ivatan	2612
iwk		I-Wak	2613
iwm		Iwam	2614
iwo		Iwur	2615
iws		Sepik Iwam	2616
ixc		Ixcatec	2617
ixl		Ixil	2618
iya		Iyayu	2619
iyo		Mesaka	2620
iyx		Yaka (Congo)	2621
izh		Ingrian	2622
izi		Izi-Ezaa-Ikwo-Mgbo	2623
izr		Izere	2624
jaa		Jamamadí	2625
jab		Hyam	2626
jac		Popti'	2627
jad		Jahanka	2628
jae		Yabem	2629
jaf		Jara	2630
jah		Jah Hut	2631
jaj		Zazao	2632
jak		Jakun	2633
jal		Yalahatan	2634
jam		Jamaican Creole English	2635
jao		Yanyuwa	2636
jaq		Yaqay	2637
jar		Jarawa (Nigeria)	2638
jas		New Caledonian Javanese	2639
jat		Jakati	2640
jau		Yaur	2641
jav	jv	Javanese	2642
jax		Jambi Malay	2643
jay		Yan-nhangu	2644
jaz		Jawe	2645
jbe		Judeo-Berber	2646
jbj		Arandai	2647
jbn		Nafusi	2648
jbo		Lojban	2649
jbr		Jofotek-Bromnya	2650
jbt		Jabutí	2651
jbu		Jukun Takum	2652
jcs		Jamaican Country Sign Language	2653
jct		Krymchak	2654
jda		Jad	2655
jdg		Jadgali	2656
jdt		Judeo-Tat	2657
jeb		Jebero	2658
jee		Jerung	2659
jeg		Jeng	2660
jeh		Jeh	2661
jei		Yei	2662
jek		Jeri Kuo	2663
jel		Yelmek	2664
jen		Dza	2665
jer		Jere	2666
jet		Manem	2667
jeu		Jonkor Bourmataguil	2668
jgb		Ngbee	2669
jge		Judeo-Georgian	2670
jgo		Ngomba	2671
jhi		Jehai	2672
jhs		Jhankot Sign Language	2673
jia		Jina	2674
jib		Jibu	2675
jic		Tol	2676
jid		Bu	2677
jie		Jilbe	2678
jig		Djingili	2679
jih		Shangzhai	2680
jii		Jiiddu	2681
jil		Jilim	2682
jim		Jimi (Cameroon)	2683
jio		Jiamao	2684
jiq		Guanyinqiao	2685
jit		Jita	2686
jiu		Youle Jinuo	2687
jiv		Shuar	2688
jiy		Buyuan Jinuo	2689
jko		Kubo	2690
jku		Labir	2691
jle		Ngile	2692
jls		Jamaican Sign Language	2693
jma		Dima	2694
jmb		Zumbun	2695
jmc		Machame	2696
jmd		Yamdena	2697
jmi		Jimi (Nigeria)	2698
jml		Jumli	2699
jmn		Makuri Naga	2700
jmr		Kamara	2701
jms		Mashi (Nigeria)	2702
jmx		Western Juxtlahuaca Mixtec	2703
jna		Jangshung	2704
jnd		Jandavra	2705
jng		Yangman	2706
jni		Janji	2707
jnj		Yemsa	2708
jnl		Rawat	2709
jns		Jaunsari	2710
job		Joba	2711
jod		Wojenaka	2712
jor		Jorá	2713
jos		Jordanian Sign Language	2714
jow		Jowulu	2715
jpa		Jewish Palestinian Aramaic	2716
jpn	ja	Japanese	2717
jpr		Judeo-Persian	2718
jqr		Jaqaru	2719
jra		Jarai	2720
jrb		Judeo-Arabic	2721
jrr		Jiru	2722
jrt		Jorto	2723
jru		Japrería	2724
jsl		Japanese Sign Language	2725
jua		Júma	2726
jub		Wannu	2727
juc		Jurchen	2728
jud		Worodougou	2729
juh		Hõne	2730
juk		Wapan	2731
jul		Jirel	2732
jum		Jumjum	2733
jun		Juang	2734
juo		Jiba	2735
jup		Hupdë	2736
jur		Jurúna	2737
jus		Jumla Sign Language	2738
jut		Jutish	2739
juu		Ju	2740
juw		Wãpha	2741
juy		Juray	2742
jvd		Javindo	2743
jvn		Caribbean Javanese	2744
jwi		Jwira-Pepesa	2745
jya		Jiarong	2746
jye		Judeo-Yemeni Arabic	2747
jyy		Jaya	2748
kaa		Kara-Kalpak	2749
kab		Kabyle	2750
kac		Kachin	2751
kad		Kadara	2752
kae		Ketangalan	2753
kaf		Katso	2754
kag		Kajaman	2755
kah		Kara (Central African Republic)	2756
kai		Karekare	2757
kaj		Jju	2758
kak		Kayapa Kallahan	2759
kal	kl	Kalaallisut	2760
kam		Kamba (Kenya)	2761
kan	kn	Kannada	2762
kao		Xaasongaxango	2763
kap		Bezhta	2764
kaq		Capanahua	2765
kas	ks	Kashmiri	2766
kat	ka	Georgian	2767
kau	kr	Kanuri	2768
kav		Katukína	2769
kaw		Kawi	2770
kax		Kao	2771
kay		Kamayurá	2772
kaz	kk	Kazakh	2773
kba		Kalarko	2774
kbb		Kaxuiâna	2775
kbc		Kadiwéu	2776
kbd		Kabardian	2777
kbe		Kanju	2778
kbf		Kakauhua	2779
kbg		Khamba	2780
kbh		Camsá	2781
kbi		Kaptiau	2782
kbj		Kari	2783
kbk		Grass Koiari	2784
kbl		Kanembu	2785
kbm		Iwal	2786
kbn		Kare (Central African Republic)	2787
kbo		Keliko	2788
kbp		Kabiyè	2789
kbq		Kamano	2790
kbr		Kafa	2791
kbs		Kande	2792
kbt		Abadi	2793
kbu		Kabutra	2794
kbv		Dera (Indonesia)	2795
kbw		Kaiep	2796
kbx		Ap Ma	2797
kby		Manga Kanuri	2798
kbz		Duhwa	2799
kca		Khanty	2800
kcb		Kawacha	2801
kcc		Lubila	2802
kcd		Ngkâlmpw Kanum	2803
kce		Kaivi	2804
kcf		Ukaan	2805
kcg		Tyap	2806
kch		Vono	2807
kci		Kamantan	2808
kcj		Kobiana	2809
kck		Kalanga	2810
kcl		Kela (Papua New Guinea)	2811
kcm		Gula (Central African Republic)	2812
kcn		Nubi	2813
kco		Kinalakna	2814
kcp		Kanga	2815
kcq		Kamo	2816
kcr		Katla	2817
kcs		Koenoem	2818
kct		Kaian	2819
kcu		Kami (Tanzania)	2820
kcv		Kete	2821
kcw		Kabwari	2822
kcx		Kachama-Ganjule	2823
kcy		Korandje	2824
kcz		Konongo	2825
kda		Worimi	2826
kdc		Kutu	2827
kdd		Yankunytjatjara	2828
kde		Makonde	2829
kdf		Mamusi	2830
kdg		Seba	2831
kdh		Tem	2832
kdi		Kumam	2833
kdj		Karamojong	2834
kdk		Numee	2835
kdl		Tsikimba	2836
kdm		Kagoma	2837
kdn		Kunda	2838
kdp		Kaningdon-Nindem	2839
kdq		Koch	2840
kdr		Karaim	2841
kdt		Kuy	2842
kdu		Kadaru	2843
kdv		Kado	2844
kdw		Koneraw	2845
kdx		Kam	2846
kdy		Keder	2847
kdz		Kwaja	2848
kea		Kabuverdianu	2849
keb		Kélé	2850
kec		Keiga	2851
ked		Kerewe	2852
kee		Eastern Keres	2853
kef		Kpessi	2854
keg		Tese	2855
keh		Keak	2856
kei		Kei	2857
kej		Kadar	2858
kek		Kekchí	2859
kel		Kela (Democratic Republic of Congo)	2860
kem		Kemak	2861
ken		Kenyang	2862
keo		Kakwa	2863
kep		Kaikadi	2864
keq		Kamar	2865
ker		Kera	2866
kes		Kugbo	2867
ket		Ket	2868
keu		Akebu	2869
kev		Kanikkaran	2870
kew		West Kewa	2871
kex		Kukna	2872
key		Kupia	2873
kez		Kukele	2874
kfa		Kodava	2875
kfb		Northwestern Kolami	2876
kfc		Konda-Dora	2877
kfd		Korra Koraga	2878
kfe		Kota (India)	2879
kff		Koya	2880
kfg		Kudiya	2881
kfh		Kurichiya	2882
kfi		Kannada Kurumba	2883
kfj		Kemiehua	2884
kfk		Kinnauri	2885
kfl		Kung	2886
kfm		Khunsari	2887
kfn		Kuk	2888
kfo		Koro (Côte d'Ivoire)	2889
kfp		Korwa	2890
kfq		Korku	2891
kfr		Kachchi	2892
kfs		Bilaspuri	2893
kft		Kanjari	2894
kfu		Katkari	2895
kfv		Kurmukar	2896
kfw		Kharam Naga	2897
kfx		Kullu Pahari	2898
kfy		Kumaoni	2899
kfz		Koromfé	2900
kga		Koyaga	2901
kgb		Kawe	2902
kgc		Kasseng	2903
kgd		Kataang	2904
kge		Komering	2905
kgf		Kube	2906
kgg		Kusunda	2907
kgh		Upper Tanudan Kalinga	2908
kgi		Selangor Sign Language	2909
kgj		Gamale Kham	2910
kgk		Kaiwá	2911
kgl		Kunggari	2912
kgm		Karipúna	2913
kgn		Karingani	2914
kgo		Krongo	2915
kgp		Kaingang	2916
kgq		Kamoro	2917
kgr		Abun	2918
kgs		Kumbainggar	2919
kgt		Somyev	2920
kgu		Kobol	2921
kgv		Karas	2922
kgw		Karon Dori	2923
kgx		Kamaru	2924
kgy		Kyerung	2925
kha		Khasi	2926
khb		Lü	2927
khc		Tukang Besi North	2928
khd		Bädi Kanum	2929
khe		Korowai	2930
khf		Khuen	2931
khg		Khams Tibetan	2932
khh		Kehu	2933
khj		Kuturmi	2934
khk		Halh Mongolian	2935
khl		Lusi	2936
khm	km	Central Khmer	2937
khn		Khandesi	2938
kho		Khotanese	2939
khp		Kapori	2940
khq		Koyra Chiini Songhay	2941
khr		Kharia	2942
khs		Kasua	2943
kht		Khamti	2944
khu		Nkhumbi	2945
khv		Khvarshi	2946
khw		Khowar	2947
khx		Kanu	2948
khy		Kele (Democratic Republic of Congo)	2949
khz		Keapara	2950
kia		Kim	2951
kib		Koalib	2952
kic		Kickapoo	2953
kid		Koshin	2954
kie		Kibet	2955
kif		Eastern Parbate Kham	2956
kig		Kimaama	2957
kih		Kilmeri	2958
kii		Kitsai	2959
kij		Kilivila	2960
kik	ki	Kikuyu	2961
kil		Kariya	2962
kim		Karagas	2963
kin	rw	Kinyarwanda	2964
kio		Kiowa	2965
kip		Sheshi Kham	2966
kiq		Kosadle	2967
kir	ky	Kirghiz	2968
kis		Kis	2969
kit		Agob	2970
kiu		Kirmanjki (individual language)	2971
kiv		Kimbu	2972
kiw		Northeast Kiwai	2973
kix		Khiamniungan Naga	2974
kiy		Kirikiri	2975
kiz		Kisi	2976
kja		Mlap	2977
kjb		Q'anjob'al	2978
kjc		Coastal Konjo	2979
kjd		Southern Kiwai	2980
kje		Kisar	2981
kjf		Khalaj	2982
kjg		Khmu	2983
kjh		Khakas	2984
kji		Zabana	2985
kjj		Khinalugh	2986
kjk		Highland Konjo	2987
kjl		Western Parbate Kham	2988
kjm		Kháng	2989
kjn		Kunjen	2990
kjo		Harijan Kinnauri	2991
kjp		Pwo Eastern Karen	2992
kjq		Western Keres	2993
kjr		Kurudu	2994
kjs		East Kewa	2995
kjt		Phrae Pwo Karen	2996
kju		Kashaya	2997
kjx		Ramopa	2998
kjy		Erave	2999
kjz		Bumthangkha	3000
kka		Kakanda	3001
kkb		Kwerisa	3002
kkc		Odoodee	3003
kkd		Kinuku	3004
kke		Kakabe	3005
kkf		Kalaktang Monpa	3006
kkg		Mabaka Valley Kalinga	3007
kkh		Khün	3008
kki		Kagulu	3009
kkj		Kako	3010
kkk		Kokota	3011
kkl		Kosarek Yale	3012
kkm		Kiong	3013
kkn		Kon Keu	3014
kko		Karko	3015
kkp		Gugubera	3016
kkq		Kaiku	3017
kkr		Kir-Balar	3018
kks		Giiwo	3019
kkt		Koi	3020
kku		Tumi	3021
kkv		Kangean	3022
kkw		Teke-Kukuya	3023
kkx		Kohin	3024
kky		Guguyimidjir	3025
kkz		Kaska	3026
kla		Klamath-Modoc	3027
klb		Kiliwa	3028
klc		Kolbila	3029
kld		Gamilaraay	3030
kle		Kulung (Nepal)	3031
klf		Kendeje	3032
klg		Tagakaulo	3033
klh		Weliki	3034
kli		Kalumpang	3035
klj		Turkic Khalaj	3036
klk		Kono (Nigeria)	3037
kll		Kagan Kalagan	3038
klm		Migum	3039
kln		Kalenjin	3040
klo		Kapya	3041
klp		Kamasa	3042
klq		Rumu	3043
klr		Khaling	3044
kls		Kalasha	3045
klt		Nukna	3046
klu		Klao	3047
klv		Maskelynes	3048
klw		Lindu	3049
klx		Koluwawa	3050
kly		Kalao	3051
klz		Kabola	3052
kma		Konni	3053
kmb		Kimbundu	3054
kmc		Southern Dong	3055
kmd		Majukayang Kalinga	3056
kme		Bakole	3057
kmf		Kare (Papua New Guinea)	3058
kmg		Kâte	3059
kmh		Kalam	3060
kmi		Kami (Nigeria)	3061
kmj		Kumarbhag Paharia	3062
kmk		Limos Kalinga	3063
kml		Lower Tanudan Kalinga	3064
kmm		Kom (India)	3065
kmn		Awtuw	3066
kmo		Kwoma	3067
kmp		Gimme	3068
kmq		Kwama	3069
kmr		Northern Kurdish	3070
kms		Kamasau	3071
kmt		Kemtuik	3072
kmu		Kanite	3073
kmv		Karipúna Creole French	3074
kmw		Komo (Democratic Republic of Congo)	3075
kmx		Waboda	3076
kmy		Koma	3077
kmz		Khorasani Turkish	3078
kna		Dera (Nigeria)	3079
knb		Lubuagan Kalinga	3080
knc		Central Kanuri	3081
knd		Konda	3082
kne		Kankanaey	3083
knf		Mankanya	3084
kng		Koongo	3085
kni		Kanufi	3086
knj		Western Kanjobal	3087
knk		Kuranko	3088
knl		Keninjal	3089
knm		Kanamarí	3090
knn		Konkani (individual language)	3091
kno		Kono (Sierra Leone)	3092
knp		Kwanja	3093
knq		Kintaq	3094
knr		Kaningra	3095
kns		Kensiu	3096
knt		Panoan Katukína	3097
knu		Kono (Guinea)	3098
knv		Tabo	3099
knw		Kung-Ekoka	3100
knx		Kendayan	3101
kny		Kanyok	3102
knz		Kalamsé	3103
koa		Konomala	3104
koc		Kpati	3105
kod		Kodi	3106
koe		Kacipo-Balesi	3107
kof		Kubi	3108
kog		Cogui	3109
koh		Koyo	3110
koi		Komi-Permyak	3111
koj		Sara Dunjo	3112
kok		Konkani (macrolanguage)	3113
kol		Kol (Papua New Guinea)	3114
kom	kv	Komi	3115
kon	kg	Kongo	3116
koo		Konzo	3117
kop		Kwato	3118
koq		Kota (Gabon)	3119
kor	ko	Korean	3120
kos		Kosraean	3121
kot		Lagwan	3122
kou		Koke	3123
kov		Kudu-Camo	3124
kow		Kugama	3125
kox		Coxima	3126
koy		Koyukon	3127
koz		Korak	3128
kpa		Kutto	3129
kpb		Mullu Kurumba	3130
kpc		Curripaco	3131
kpd		Koba	3132
kpe		Kpelle	3133
kpf		Komba	3134
kpg		Kapingamarangi	3135
kph		Kplang	3136
kpi		Kofei	3137
kpj		Karajá	3138
kpk		Kpan	3139
kpl		Kpala	3140
kpm		Koho	3141
kpn		Kepkiriwát	3142
kpo		Ikposo	3143
kpp		Paku Karen	3144
kpq		Korupun-Sela	3145
kpr		Korafe-Yegha	3146
kps		Tehit	3147
kpt		Karata	3148
kpu		Kafoa	3149
kpv		Komi-Zyrian	3150
kpw		Kobon	3151
kpx		Mountain Koiali	3152
kpy		Koryak	3153
kpz		Kupsabiny	3154
kqa		Mum	3155
kqb		Kovai	3156
kqc		Doromu-Koki	3157
kqd		Koy Sanjaq Surat	3158
kqe		Kalagan	3159
kqf		Kakabai	3160
kqg		Khe	3161
kqh		Kisankasa	3162
kqi		Koitabu	3163
kqj		Koromira	3164
kqk		Kotafon Gbe	3165
kql		Kyenele	3166
kqm		Khisa	3167
kqn		Kaonde	3168
kqo		Eastern Krahn	3169
kqp		Kimré	3170
kqq		Krenak	3171
kqr		Kimaragang	3172
kqs		Northern Kissi	3173
kqt		Klias River Kadazan	3174
kqu		Seroa	3175
kqv		Okolod	3176
kqw		Kandas	3177
kqx		Mser	3178
kqy		Koorete	3179
kqz		Korana	3180
kra		Kumhali	3181
krb		Karkin	3182
krc		Karachay-Balkar	3183
krd		Kairui-Midiki	3184
kre		Panará	3185
krf		Koro (Vanuatu)	3186
krh		Kurama	3187
kri		Krio	3188
krj		Kinaray-A	3189
krk		Kerek	3190
krl		Karelian	3191
krm		Krim	3192
krn		Sapo	3193
krp		Korop	3194
krr		Kru'ng 2	3195
krs		Gbaya (Sudan)	3196
krt		Tumari Kanuri	3197
kru		Kurukh	3198
krv		Kavet	3199
krw		Western Krahn	3200
krx		Karon	3201
kry		Kryts	3202
krz		Sota Kanum	3203
ksa		Shuwa-Zamani	3204
ksb		Shambala	3205
ksc		Southern Kalinga	3206
ksd		Kuanua	3207
kse		Kuni	3208
ksf		Bafia	3209
ksg		Kusaghe	3210
ksh		Kölsch	3211
ksi		Krisa	3212
ksj		Uare	3213
ksk		Kansa	3214
ksl		Kumalu	3215
ksm		Kumba	3216
ksn		Kasiguranin	3217
kso		Kofa	3218
ksp		Kaba	3219
ksq		Kwaami	3220
ksr		Borong	3221
kss		Southern Kisi	3222
kst		Winyé	3223
ksu		Khamyang	3224
ksv		Kusu	3225
ksw		S'gaw Karen	3226
ksx		Kedang	3227
ksy		Kharia Thar	3228
ksz		Kodaku	3229
kta		Katua	3230
ktb		Kambaata	3231
ktc		Kholok	3232
ktd		Kokata	3233
kte		Nubri	3234
ktf		Kwami	3235
ktg		Kalkutung	3236
kth		Karanga	3237
kti		North Muyu	3238
ktj		Plapo Krumen	3239
ktk		Kaniet	3240
ktl		Koroshi	3241
ktm		Kurti	3242
ktn		Karitiâna	3243
kto		Kuot	3244
ktp		Kaduo	3245
ktq		Katabaga	3246
ktr		Kota Marudu Tinagas	3247
kts		South Muyu	3248
ktt		Ketum	3249
ktu		Kituba (Democratic Republic of Congo)	3250
ktv		Eastern Katu	3251
ktw		Kato	3252
ktx		Kaxararí	3253
kty		Kango (Bas-Uélé District)	3254
ktz		Ju/'hoan	3255
kua	kj	Kuanyama	3256
kub		Kutep	3257
kuc		Kwinsu	3258
kud		'Auhelawa	3259
kue		Kuman	3260
kuf		Western Katu	3261
kug		Kupa	3262
kuh		Kushi	3263
kui		Kuikúro-Kalapálo	3264
kuj		Kuria	3265
kuk		Kepo'	3266
kul		Kulere	3267
kum		Kumyk	3268
kun		Kunama	3269
kuo		Kumukio	3270
kup		Kunimaipa	3271
kuq		Karipuna	3272
kur	ku	Kurdish	3273
kus		Kusaal	3274
kut		Kutenai	3275
kuu		Upper Kuskokwim	3276
kuv		Kur	3277
kuw		Kpagua	3278
kux		Kukatja	3279
kuy		Kuuku-Ya'u	3280
kuz		Kunza	3281
kva		Bagvalal	3282
kvb		Kubu	3283
kvc		Kove	3284
kvd		Kui (Indonesia)	3285
kve		Kalabakan	3286
kvf		Kabalai	3287
kvg		Kuni-Boazi	3288
kvh		Komodo	3289
kvi		Kwang	3290
kvj		Psikye	3291
kvk		Korean Sign Language	3292
kvl		Brek Karen	3293
kvm		Kendem	3294
kvn		Border Kuna	3295
kvo		Dobel	3296
kvp		Kompane	3297
kvq		Geba Karen	3298
kvr		Kerinci	3299
kvs		Kunggara	3300
kvt		Lahta Karen	3301
kvu		Yinbaw Karen	3302
kvv		Kola	3303
kvw		Wersing	3304
kvx		Parkari Koli	3305
kvy		Yintale Karen	3306
kvz		Tsakwambo	3307
kwa		Dâw	3308
kwb		Kwa	3309
kwc		Likwala	3310
kwd		Kwaio	3311
kwe		Kwerba	3312
kwf		Kwara'ae	3313
kwg		Sara Kaba Deme	3314
kwh		Kowiai	3315
kwi		Awa-Cuaiquer	3316
kwj		Kwanga	3317
kwk		Kwakiutl	3318
kwl		Kofyar	3319
kwm		Kwambi	3320
kwn		Kwangali	3321
kwo		Kwomtari	3322
kwp		Kodia	3323
kwq		Kwak	3324
kwr		Kwer	3325
kws		Kwese	3326
kwt		Kwesten	3327
kwu		Kwakum	3328
kwv		Sara Kaba Náà	3329
kww		Kwinti	3330
kwx		Khirwar	3331
kwy		San Salvador Kongo	3332
kwz		Kwadi	3333
kxa		Kairiru	3334
kxb		Krobu	3335
kxc		Konso	3336
kxd		Brunei	3337
kxe		Kakihum	3338
kxf		Manumanaw Karen	3339
kxh		Karo (Ethiopia)	3340
kxi		Keningau Murut	3341
kxj		Kulfa	3342
kxk		Zayein Karen	3343
kxl		Nepali Kurux	3344
kxm		Northern Khmer	3345
kxn		Kanowit-Tanjong Melanau	3346
kxo		Kanoé	3347
kxp		Wadiyara Koli	3348
kxq		Smärky Kanum	3349
kxr		Koro (Papua New Guinea)	3350
kxs		Kangjia	3351
kxt		Koiwat	3352
kxu		Kui (India)	3353
kxv		Kuvi	3354
kxw		Konai	3355
kxx		Likuba	3356
kxy		Kayong	3357
kxz		Kerewo	3358
kya		Kwaya	3359
kyb		Butbut Kalinga	3360
kyc		Kyaka	3361
kyd		Karey	3362
kye		Krache	3363
kyf		Kouya	3364
kyg		Keyagana	3365
kyh		Karok	3366
kyi		Kiput	3367
kyj		Karao	3368
kyk		Kamayo	3369
kyl		Kalapuya	3370
kym		Kpatili	3371
kyn		Northern Binukidnon	3372
kyo		Kelon	3373
kyp		Kang	3374
kyq		Kenga	3375
kyr		Kuruáya	3376
kys		Baram Kayan	3377
kyt		Kayagar	3378
kyu		Western Kayah	3379
kyv		Kayort	3380
kyw		Kudmali	3381
kyx		Rapoisi	3382
kyy		Kambaira	3383
kyz		Kayabí	3384
kza		Western Karaboro	3385
kzb		Kaibobo	3386
kzc		Bondoukou Kulango	3387
kzd		Kadai	3388
kze		Kosena	3389
kzf		Da'a Kaili	3390
kzg		Kikai	3391
kzh		Kenuzi-Dongola	3392
kzi		Kelabit	3393
kzj		Coastal Kadazan	3394
kzk		Kazukuru	3395
kzl		Kayeli	3396
kzm		Kais	3397
kzn		Kokola	3398
kzo		Kaningi	3399
kzp		Kaidipang	3400
kzq		Kaike	3401
kzr		Karang	3402
kzs		Sugut Dusun	3403
kzt		Tambunan Dusun	3404
kzu		Kayupulau	3405
kzv		Komyandaret	3406
kzw		Karirí-Xocó	3407
kzx		Kamarian	3408
kzy		Kango (Tshopo District)	3409
kzz		Kalabra	3410
laa		Southern Subanen	3411
lab		Linear A	3412
lac		Lacandon	3413
lad		Ladino	3414
lae		Pattani	3415
laf		Lafofa	3416
lag		Langi	3417
lah		Lahnda	3418
lai		Lambya	3419
laj		Lango (Uganda)	3420
lak		Laka (Nigeria)	3421
lal		Lalia	3422
lam		Lamba	3423
lan		Laru	3424
lao	lo	Lao	3425
lap		Laka (Chad)	3426
laq		Qabiao	3427
lar		Larteh	3428
las		Lama (Togo)	3429
lat	la	Latin	3430
lau		Laba	3431
lav	lv	Latvian	3432
law		Lauje	3433
lax		Tiwa	3434
lay		Lama (Myanmar)	3435
laz		Aribwatsa	3436
lba		Lui	3437
lbb		Label	3438
lbc		Lakkia	3439
lbe		Lak	3440
lbf		Tinani	3441
lbg		Laopang	3442
lbi		La'bi	3443
lbj		Ladakhi	3444
lbk		Central Bontok	3445
lbl		Libon Bikol	3446
lbm		Lodhi	3447
lbn		Lamet	3448
lbo		Laven	3449
lbq		Wampar	3450
lbr		Northern Lorung	3451
lbs		Libyan Sign Language	3452
lbt		Lachi	3453
lbu		Labu	3454
lbv		Lavatbura-Lamusong	3455
lbw		Tolaki	3456
lbx		Lawangan	3457
lby		Lamu-Lamu	3458
lbz		Lardil	3459
lcc		Legenyem	3460
lcd		Lola	3461
lce		Loncong	3462
lcf		Lubu	3463
lch		Luchazi	3464
lcl		Lisela	3465
lcm		Tungag	3466
lcp		Western Lawa	3467
lcq		Luhu	3468
lcs		Lisabata-Nuniali	3469
ldb		Idun	3470
ldd		Luri	3471
ldg		Lenyima	3472
ldh		Lamja-Dengsa-Tola	3473
ldi		Laari	3474
ldj		Lemoro	3475
ldk		Leelau	3476
ldl		Kaan	3477
ldm		Landoma	3478
ldn		Láadan	3479
ldo		Loo	3480
ldp		Tso	3481
ldq		Lufu	3482
lea		Lega-Shabunda	3483
leb		Lala-Bisa	3484
lec		Leco	3485
led		Lendu	3486
lee		Lyélé	3487
lef		Lelemi	3488
leg		Lengua	3489
leh		Lenje	3490
lei		Lemio	3491
lej		Lengola	3492
lek		Leipon	3493
lel		Lele (Democratic Republic of Congo)	3494
lem		Nomaande	3495
len		Lenca	3496
leo		Leti (Cameroon)	3497
lep		Lepcha	3498
leq		Lembena	3499
ler		Lenkau	3500
les		Lese	3501
let		Lesing-Gelimi	3502
leu		Kara (Papua New Guinea)	3503
lev		Lamma	3504
lew		Ledo Kaili	3505
lex		Luang	3506
ley		Lemolang	3507
lez		Lezghian	3508
lfa		Lefa	3509
lfn		Lingua Franca Nova	3510
lga		Lungga	3511
lgb		Laghu	3512
lgg		Lugbara	3513
lgh		Laghuu	3514
lgi		Lengilu	3515
lgk		Lingarak	3516
lgl		Wala	3517
lgm		Lega-Mwenga	3518
lgn		Opuuo	3519
lgq		Logba	3520
lgr		Lengo	3521
lgt		Pahi	3522
lgu		Longgu	3523
lgz		Ligenza	3524
lha		Laha (Viet Nam)	3525
lhh		Laha (Indonesia)	3526
lhi		Lahu Shi	3527
lhl		Lahul Lohar	3528
lhm		Lhomi	3529
lhn		Lahanan	3530
lhp		Lhokpu	3531
lhs		Mlahsö	3532
lht		Lo-Toga	3533
lhu		Lahu	3534
lia		West-Central Limba	3535
lib		Likum	3536
lic		Hlai	3537
lid		Nyindrou	3538
lie		Likila	3539
lif		Limbu	3540
lig		Ligbi	3541
lih		Lihir	3542
lii		Lingkhim	3543
lij		Ligurian	3544
lik		Lika	3545
lil		Lillooet	3546
lim	li	Limburgan	3547
lin	ln	Lingala	3548
lio		Liki	3549
lip		Sekpele	3550
liq		Libido	3551
lir		Liberian English	3552
lis		Lisu	3553
lit	lt	Lithuanian	3554
liu		Logorik	3555
liv		Liv	3556
liw		Col	3557
lix		Liabuku	3558
liy		Banda-Bambari	3559
liz		Libinza	3560
lje		Rampi	3561
lji		Laiyolo	3562
ljl		Li'o	3563
ljp		Lampung Api	3564
lka		Lakalei	3565
lkb		Kabras	3566
lkc		Kucong	3567
lkd		Lakondê	3568
lke		Kenyi	3569
lkh		Lakha	3570
lki		Laki	3571
lkj		Remun	3572
lkl		Laeko-Libuat	3573
lkn		Lakon	3574
lko		Khayo	3575
lkr		Päri	3576
lks		Kisa	3577
lkt		Lakota	3578
lky		Lokoya	3579
lla		Lala-Roba	3580
llb		Lolo	3581
llc		Lele (Guinea)	3582
lld		Ladin	3583
lle		Lele (Papua New Guinea)	3584
llf		Hermit	3585
llg		Lole	3586
llh		Lamu	3587
lli		Teke-Laali	3588
llk		Lelak	3589
lll		Lilau	3590
llm		Lasalimu	3591
lln		Lele (Chad)	3592
llo		Khlor	3593
llp		North Efate	3594
llq		Lolak	3595
lls		Lithuanian Sign Language	3596
llu		Lau	3597
llx		Lauan	3598
lma		East Limba	3599
lmb		Merei	3600
lmc		Limilngan	3601
lmd		Lumun	3602
lme		Pévé	3603
lmf		South Lembata	3604
lmg		Lamogai	3605
lmh		Lambichhong	3606
lmi		Lombi	3607
lmj		West Lembata	3608
lmk		Lamkang	3609
lml		Hano	3610
lmm		Lamam	3611
lmn		Lambadi	3612
lmo		Lombard	3613
lmp		Limbum	3614
lmq		Lamatuka	3615
lmr		Lamalera	3616
lmu		Lamenu	3617
lmv		Lomaiviti	3618
lmw		Lake Miwok	3619
lmx		Laimbue	3620
lmy		Lamboya	3621
lmz		Lumbee	3622
lna		Langbashe	3623
lnb		Mbalanhu	3624
lnd		Lundayeh	3625
lng		Langobardic	3626
lnh		Lanoh	3627
lni		Daantanai'	3628
lnj		Leningitij	3629
lnl		South Central Banda	3630
lnm		Langam	3631
lnn		Lorediakarkar	3632
lno		Lango (Sudan)	3633
lns		Lamnso'	3634
lnu		Longuda	3635
lnz		Lonzo	3636
loa		Loloda	3637
lob		Lobi	3638
loc		Inonhan	3639
loe		Saluan	3640
lof		Logol	3641
log		Logo	3642
loh		Narim	3643
loi		Loma (Côte d'Ivoire)	3644
loj		Lou	3645
lok		Loko	3646
lol		Mongo	3647
lom		Loma (Liberia)	3648
lon		Malawi Lomwe	3649
loo		Lombo	3650
lop		Lopa	3651
loq		Lobala	3652
lor		Téén	3653
los		Loniu	3654
lot		Otuho	3655
lou		Louisiana Creole French	3656
lov		Lopi	3657
low		Tampias Lobu	3658
lox		Loun	3659
loy		Lowa	3660
loz		Lozi	3661
lpa		Lelepa	3662
lpe		Lepki	3663
lpn		Long Phuri Naga	3664
lpo		Lipo	3665
lpx		Lopit	3666
lra		Rara Bakati'	3667
lrc		Northern Luri	3668
lre		Laurentian	3669
lrg		Laragia	3670
lri		Marachi	3671
lrk		Loarki	3672
lrl		Lari	3673
lrm		Marama	3674
lrn		Lorang	3675
lro		Laro	3676
lrr		Southern Lorung	3677
lrt		Larantuka Malay	3678
lrv		Larevat	3679
lrz		Lemerig	3680
lsa		Lasgerdi	3681
lsd		Lishana Deni	3682
lse		Lusengo	3683
lsg		Lyons Sign Language	3684
lsh		Lish	3685
lsi		Lashi	3686
lsl		Latvian Sign Language	3687
lsm		Saamia	3688
lso		Laos Sign Language	3689
lsp		Panamanian Sign Language	3690
lsr		Aruop	3691
lss		Lasi	3692
lst		Trinidad and Tobago Sign Language	3693
lsy		Mauritian Sign Language	3694
ltc		Late Middle Chinese	3695
ltg		Latgalian	3696
lti		Leti (Indonesia)	3697
ltn		Latundê	3698
lto		Tsotso	3699
lts		Tachoni	3700
ltu		Latu	3701
ltz	lb	Luxembourgish	3702
lua		Luba-Lulua	3703
lub	lu	Luba-Katanga	3704
luc		Aringa	3705
lud		Ludian	3706
lue		Luvale	3707
luf		Laua	3708
lug	lg	Ganda	3709
lui		Luiseno	3710
luj		Luna	3711
luk		Lunanakha	3712
lul		Olu'bo	3713
lum		Luimbi	3714
lun		Lunda	3715
luo		Luo (Kenya and Tanzania)	3716
lup		Lumbu	3717
luq		Lucumi	3718
lur		Laura	3719
lus		Lushai	3720
lut		Lushootseed	3721
luu		Lumba-Yakkha	3722
luv		Luwati	3723
luw		Luo (Cameroon)	3724
luy		Luyia	3725
luz		Southern Luri	3726
lva		Maku'a	3727
lvk		Lavukaleve	3728
lvs		Standard Latvian	3729
lvu		Levuka	3730
lwa		Lwalu	3731
lwe		Lewo Eleng	3732
lwg		Wanga	3733
lwh		White Lachi	3734
lwl		Eastern Lawa	3735
lwm		Laomian	3736
lwo		Luwo	3737
lwt		Lewotobi	3738
lww		Lewo	3739
lya		Layakha	3740
lyg		Lyngngam	3741
lyn		Luyana	3742
lzh		Literary Chinese	3743
lzl		Litzlitz	3744
lzn		Leinong Naga	3745
lzz		Laz	3746
maa		San Jerónimo Tecóatl Mazatec	3747
mab		Yutanduchi Mixtec	3748
mad		Madurese	3749
mae		Bo-Rukul	3750
maf		Mafa	3751
mag		Magahi	3752
mah	mh	Marshallese	3753
mai		Maithili	3754
maj		Jalapa De Díaz Mazatec	3755
mak		Makasar	3756
mal	ml	Malayalam	3757
mam		Mam	3758
man		Mandingo	3759
maq		Chiquihuitlán Mazatec	3760
mar	mr	Marathi	3761
mas		Masai	3762
mat		San Francisco Matlatzinca	3763
mau		Huautla Mazatec	3764
mav		Sateré-Mawé	3765
maw		Mampruli	3766
max		North Moluccan Malay	3767
maz		Central Mazahua	3768
mba		Higaonon	3769
mbb		Western Bukidnon Manobo	3770
mbc		Macushi	3771
mbd		Dibabawon Manobo	3772
mbe		Molale	3773
mbf		Baba Malay	3774
mbh		Mangseng	3775
mbi		Ilianen Manobo	3776
mbj		Nadëb	3777
mbk		Malol	3778
mbl		Maxakalí	3779
mbm		Ombamba	3780
mbn		Macaguán	3781
mbo		Mbo (Cameroon)	3782
mbp		Malayo	3783
mbq		Maisin	3784
mbr		Nukak Makú	3785
mbs		Sarangani Manobo	3786
mbt		Matigsalug Manobo	3787
mbu		Mbula-Bwazza	3788
mbv		Mbulungish	3789
mbw		Maring	3790
mbx		Mari (East Sepik Province)	3791
mby		Memoni	3792
mbz		Amoltepec Mixtec	3793
mca		Maca	3794
mcb		Machiguenga	3795
mcc		Bitur	3796
mcd		Sharanahua	3797
mce		Itundujia Mixtec	3798
mcf		Matsés	3799
mcg		Mapoyo	3800
mch		Maquiritari	3801
mci		Mese	3802
mcj		Mvanip	3803
mck		Mbunda	3804
mcl		Macaguaje	3805
mcm		Malaccan Creole Portuguese	3806
mcn		Masana	3807
mco		Coatlán Mixe	3808
mcp		Makaa	3809
mcq		Ese	3810
mcr		Menya	3811
mcs		Mambai	3812
mct		Mengisa	3813
mcu		Cameroon Mambila	3814
mcv		Minanibai	3815
mcw		Mawa (Chad)	3816
mcx		Mpiemo	3817
mcy		South Watut	3818
mcz		Mawan	3819
mda		Mada (Nigeria)	3820
mdb		Morigi	3821
mdc		Male (Papua New Guinea)	3822
mdd		Mbum	3823
mde		Maba (Chad)	3824
mdf		Moksha	3825
mdg		Massalat	3826
mdh		Maguindanaon	3827
mdi		Mamvu	3828
mdj		Mangbetu	3829
mdk		Mangbutu	3830
mdl		Maltese Sign Language	3831
mdm		Mayogo	3832
mdn		Mbati	3833
mdp		Mbala	3834
mdq		Mbole	3835
mdr		Mandar	3836
mds		Maria (Papua New Guinea)	3837
mdt		Mbere	3838
mdu		Mboko	3839
mdv		Santa Lucía Monteverde Mixtec	3840
mdw		Mbosi	3841
mdx		Dizin	3842
mdy		Male (Ethiopia)	3843
mdz		Suruí Do Pará	3844
mea		Menka	3845
meb		Ikobi-Mena	3846
mec		Mara	3847
med		Melpa	3848
mee		Mengen	3849
mef		Megam	3850
meg		Mea	3851
meh		Southwestern Tlaxiaco Mixtec	3852
mei		Midob	3853
mej		Meyah	3854
mek		Mekeo	3855
mel		Central Melanau	3856
mem		Mangala	3857
men		Mende (Sierra Leone)	3858
meo		Kedah Malay	3859
mep		Miriwung	3860
meq		Merey	3861
mer		Meru	3862
mes		Masmaje	3863
met		Mato	3864
meu		Motu	3865
mev		Mann	3866
mew		Maaka	3867
mey		Hassaniyya	3868
mez		Menominee	3869
mfa		Pattani Malay	3870
mfb		Bangka	3871
mfc		Mba	3872
mfd		Mendankwe-Nkwen	3873
mfe		Morisyen	3874
mff		Naki	3875
mfg		Mixifore	3876
mfh		Matal	3877
mfi		Wandala	3878
mfj		Mefele	3879
mfk		North Mofu	3880
mfl		Putai	3881
mfm		Marghi South	3882
mfn		Cross River Mbembe	3883
mfo		Mbe	3884
mfp		Makassar Malay	3885
mfq		Moba	3886
mfr		Marithiel	3887
mfs		Mexican Sign Language	3888
mft		Mokerang	3889
mfu		Mbwela	3890
mfv		Mandjak	3891
mfw		Mulaha	3892
mfx		Melo	3893
mfy		Mayo	3894
mfz		Mabaan	3895
mga		Middle Irish (900-1200)	3896
mgb		Mararit	3897
mgc		Morokodo	3898
mgd		Moru	3899
mge		Mango	3900
mgf		Maklew	3901
mgg		Mpongmpong	3902
mgh		Makhuwa-Meetto	3903
mgi		Lijili	3904
mgj		Abureni	3905
mgk		Mawes	3906
mgl		Maleu-Kilenge	3907
mgm		Mambae	3908
mgn		Mbangi	3909
mgo		Meta'	3910
mgp		Eastern Magar	3911
mgq		Malila	3912
mgr		Mambwe-Lungu	3913
mgs		Manda (Tanzania)	3914
mgt		Mongol	3915
mgu		Mailu	3916
mgv		Matengo	3917
mgw		Matumbi	3918
mgx		Omati	3919
mgy		Mbunga	3920
mgz		Mbugwe	3921
mha		Manda (India)	3922
mhb		Mahongwe	3923
mhc		Mocho	3924
mhd		Mbugu	3925
mhe		Besisi	3926
mhf		Mamaa	3927
mhg		Margu	3928
mhh		Maskoy Pidgin	3929
mhi		Ma'di	3930
mhj		Mogholi	3931
mhk		Mungaka	3932
mhl		Mauwake	3933
mhm		Makhuwa-Moniga	3934
mhn		Mócheno	3935
mho		Mashi (Zambia)	3936
mhp		Balinese Malay	3937
mhq		Mandan	3938
mhr		Eastern Mari	3939
mhs		Buru (Indonesia)	3940
mht		Mandahuaca	3941
mhu		Digaro-Mishmi	3942
mhw		Mbukushu	3943
mhx		Maru	3944
mhy		Ma'anyan	3945
mhz		Mor (Mor Islands)	3946
mia		Miami	3947
mib		Atatláhuca Mixtec	3948
mic		Mi'kmaq	3949
mid		Mandaic	3950
mie		Ocotepec Mixtec	3951
mif		Mofu-Gudur	3952
mig		San Miguel El Grande Mixtec	3953
mih		Chayuco Mixtec	3954
mii		Chigmecatitlán Mixtec	3955
mij		Abar	3956
mik		Mikasuki	3957
mil		Peñoles Mixtec	3958
mim		Alacatlatzala Mixtec	3959
min		Minangkabau	3960
mio		Pinotepa Nacional Mixtec	3961
mip		Apasco-Apoala Mixtec	3962
miq		Mískito	3963
mir		Isthmus Mixe	3964
mis		Uncoded languages	3965
mit		Southern Puebla Mixtec	3966
miu		Cacaloxtepec Mixtec	3967
miw		Akoye	3968
mix		Mixtepec Mixtec	3969
miy		Ayutla Mixtec	3970
miz		Coatzospan Mixtec	3971
mja		Mahei	3972
mjc		San Juan Colorado Mixtec	3973
mjd		Northwest Maidu	3974
mje		Muskum	3975
mjg		Tu	3976
mjh		Mwera (Nyasa)	3977
mji		Kim Mun	3978
mjj		Mawak	3979
mjk		Matukar	3980
mjl		Mandeali	3981
mjm		Medebur	3982
mjn		Ma (Papua New Guinea)	3983
mjo		Malankuravan	3984
mjp		Malapandaram	3985
mjq		Malaryan	3986
mjr		Malavedan	3987
mjs		Miship	3988
mjt		Sauria Paharia	3989
mju		Manna-Dora	3990
mjv		Mannan	3991
mjw		Karbi	3992
mjx		Mahali	3993
mjy		Mahican	3994
mjz		Majhi	3995
mka		Mbre	3996
mkb		Mal Paharia	3997
mkc		Siliput	3998
mkd	mk	Macedonian	3999
mke		Mawchi	4000
mkf		Miya	4001
mkg		Mak (China)	4002
mki		Dhatki	4003
mkj		Mokilese	4004
mkk		Byep	4005
mkl		Mokole	4006
mkm		Moklen	4007
mkn		Kupang Malay	4008
mko		Mingang Doso	4009
mkp		Moikodi	4010
mkq		Bay Miwok	4011
mkr		Malas	4012
mks		Silacayoapan Mixtec	4013
mkt		Vamale	4014
mku		Konyanka Maninka	4015
mkv		Mafea	4016
mkw		Kituba (Congo)	4017
mkx		Kinamiging Manobo	4018
mky		East Makian	4019
mkz		Makasae	4020
mla		Malo	4021
mlb		Mbule	4022
mlc		Cao Lan	4023
mld		Malakhel	4024
mle		Manambu	4025
mlf		Mal	4026
mlg	mg	Malagasy	4027
mlh		Mape	4028
mli		Malimpung	4029
mlj		Miltu	4030
mlk		Ilwana	4031
mll		Malua Bay	4032
mlm		Mulam	4033
mln		Malango	4034
mlo		Mlomp	4035
mlp		Bargam	4036
mlq		Western Maninkakan	4037
mlr		Vame	4038
mls		Masalit	4039
mlt	mt	Maltese	4040
mlu		To'abaita	4041
mlv		Motlav	4042
mlw		Moloko	4043
mlx		Malfaxal	4044
mlz		Malaynon	4045
mma		Mama	4046
mmb		Momina	4047
mmc		Michoacán Mazahua	4048
mmd		Maonan	4049
mme		Mae	4050
mmf		Mundat	4051
mmg		North Ambrym	4052
mmh		Mehináku	4053
mmi		Musar	4054
mmj		Majhwar	4055
mmk		Mukha-Dora	4056
mml		Man Met	4057
mmm		Maii	4058
mmn		Mamanwa	4059
mmo		Mangga Buang	4060
mmp		Siawi	4061
mmq		Musak	4062
mmr		Western Xiangxi Miao	4063
mmt		Malalamai	4064
mmu		Mmaala	4065
mmv		Miriti	4066
mmw		Emae	4067
mmx		Madak	4068
mmy		Migaama	4069
mmz		Mabaale	4070
mna		Mbula	4071
mnb		Muna	4072
mnc		Manchu	4073
mnd		Mondé	4074
mne		Naba	4075
mnf		Mundani	4076
mng		Eastern Mnong	4077
mnh		Mono (Democratic Republic of Congo)	4078
mni		Manipuri	4079
mnj		Munji	4080
mnk		Mandinka	4081
mnl		Tiale	4082
mnm		Mapena	4083
mnn		Southern Mnong	4084
mnp		Min Bei Chinese	4085
mnq		Minriq	4086
mnr		Mono (USA)	4087
mns		Mansi	4088
mnt		Maykulan	4089
mnu		Mer	4090
mnv		Rennell-Bellona	4091
mnw		Mon	4092
mnx		Manikion	4093
mny		Manyawa	4094
mnz		Moni	4095
moa		Mwan	4096
moc		Mocoví	4097
mod		Mobilian	4098
moe		Montagnais	4099
mog		Mongondow	4100
moh		Mohawk	4101
moi		Mboi	4102
moj		Monzombo	4103
mok		Morori	4104
mom		Mangue	4105
mon	mn	Mongolian	4106
moo		Monom	4107
mop		Mopán Maya	4108
moq		Mor (Bomberai Peninsula)	4109
mor		Moro	4110
mos		Mossi	4111
mot		Barí	4112
mou		Mogum	4113
mov		Mohave	4114
mow		Moi (Congo)	4115
mox		Molima	4116
moy		Shekkacho	4117
moz		Mukulu	4118
mpa		Mpoto	4119
mpb		Mullukmulluk	4120
mpc		Mangarayi	4121
mpd		Machinere	4122
mpe		Majang	4123
mpg		Marba	4124
mph		Maung	4125
mpi		Mpade	4126
mpj		Martu Wangka	4127
mpk		Mbara (Chad)	4128
mpl		Middle Watut	4129
mpm		Yosondúa Mixtec	4130
mpn		Mindiri	4131
mpo		Miu	4132
mpp		Migabac	4133
mpq		Matís	4134
mpr		Vangunu	4135
mps		Dadibi	4136
mpt		Mian	4137
mpu		Makuráp	4138
mpv		Mungkip	4139
mpw		Mapidian	4140
mpx		Misima-Paneati	4141
mpy		Mapia	4142
mpz		Mpi	4143
mqa		Maba (Indonesia)	4144
mqb		Mbuko	4145
mqc		Mangole	4146
mqe		Matepi	4147
mqf		Momuna	4148
mqg		Kota Bangun Kutai Malay	4149
mqh		Tlazoyaltepec Mixtec	4150
mqi		Mariri	4151
mqj		Mamasa	4152
mqk		Rajah Kabunsuwan Manobo	4153
mql		Mbelime	4154
mqm		South Marquesan	4155
mqn		Moronene	4156
mqo		Modole	4157
mqp		Manipa	4158
mqq		Minokok	4159
mqr		Mander	4160
mqs		West Makian	4161
mqt		Mok	4162
mqu		Mandari	4163
mqv		Mosimo	4164
mqw		Murupi	4165
mqx		Mamuju	4166
mqy		Manggarai	4167
mqz		Malasanga	4168
mra		Mlabri	4169
mrb		Marino	4170
mrc		Maricopa	4171
mrd		Western Magar	4172
mre		Martha's Vineyard Sign Language	4173
mrf		Elseng	4174
mrg		Miri	4175
mrh		Mara Chin	4176
mri	mi	Maori	4177
mrj		Western Mari	4178
mrk		Hmwaveke	4179
mrl		Mortlockese	4180
mrm		Merlav	4181
mrn		Cheke Holo	4182
mro		Mru	4183
mrp		Morouas	4184
mrq		North Marquesan	4185
mrr		Maria (India)	4186
mrs		Maragus	4187
mrt		Marghi Central	4188
mru		Mono (Cameroon)	4189
mrv		Mangareva	4190
mrw		Maranao	4191
mrx		Maremgi	4192
mry		Mandaya	4193
mrz		Marind	4194
msa	ms	Malay (macrolanguage)	4195
msb		Masbatenyo	4196
msc		Sankaran Maninka	4197
msd		Yucatec Maya Sign Language	4198
mse		Musey	4199
msf		Mekwei	4200
msg		Moraid	4201
msh		Masikoro Malagasy	4202
msi		Sabah Malay	4203
msj		Ma (Democratic Republic of Congo)	4204
msk		Mansaka	4205
msl		Molof	4206
msm		Agusan Manobo	4207
msn		Vurës	4208
mso		Mombum	4209
msp		Maritsauá	4210
msq		Caac	4211
msr		Mongolian Sign Language	4212
mss		West Masela	4213
msu		Musom	4214
msv		Maslam	4215
msw		Mansoanka	4216
msx		Moresada	4217
msy		Aruamu	4218
msz		Momare	4219
mta		Cotabato Manobo	4220
mtb		Anyin Morofo	4221
mtc		Munit	4222
mtd		Mualang	4223
mte		Mono (Solomon Islands)	4224
mtf		Murik (Papua New Guinea)	4225
mtg		Una	4226
mth		Munggui	4227
mti		Maiwa (Papua New Guinea)	4228
mtj		Moskona	4229
mtk		Mbe'	4230
mtl		Montol	4231
mtm		Mator	4232
mtn		Matagalpa	4233
mto		Totontepec Mixe	4234
mtp		Wichí Lhamtés Nocten	4235
mtq		Muong	4236
mtr		Mewari	4237
mts		Yora	4238
mtt		Mota	4239
mtu		Tututepec Mixtec	4240
mtv		Asaro'o	4241
mtw		Southern Binukidnon	4242
mtx		Tidaá Mixtec	4243
mty		Nabi	4244
mua		Mundang	4245
mub		Mubi	4246
muc		Mbu'	4247
mud		Mednyj Aleut	4248
mue		Media Lengua	4249
mug		Musgu	4250
muh		Mündü	4251
mui		Musi	4252
muj		Mabire	4253
muk		Mugom	4254
mul		Multiple languages	4255
mum		Maiwala	4256
muo		Nyong	4257
mup		Malvi	4258
muq		Eastern Xiangxi Miao	4259
mur		Murle	4260
mus		Creek	4261
mut		Western Muria	4262
muu		Yaaku	4263
muv		Muthuvan	4264
mux		Bo-Ung	4265
muy		Muyang	4266
muz		Mursi	4267
mva		Manam	4268
mvb		Mattole	4269
mvd		Mamboru	4270
mve		Marwari (Pakistan)	4271
mvf		Peripheral Mongolian	4272
mvg		Yucuañe Mixtec	4273
mvh		Mire	4274
mvi		Miyako	4275
mvk		Mekmek	4276
mvl		Mbara (Australia)	4277
mvm		Muya	4278
mvn		Minaveha	4279
mvo		Marovo	4280
mvp		Duri	4281
mvq		Moere	4282
mvr		Marau	4283
mvs		Massep	4284
mvt		Mpotovoro	4285
mvu		Marfa	4286
mvv		Tagal Murut	4287
mvw		Machinga	4288
mvx		Meoswar	4289
mvy		Indus Kohistani	4290
mvz		Mesqan	4291
mwa		Mwatebu	4292
mwb		Juwal	4293
mwc		Are	4294
mwd		Mudbura	4295
mwe		Mwera (Chimwera)	4296
mwf		Murrinh-Patha	4297
mwg		Aiklep	4298
mwh		Mouk-Aria	4299
mwi		Labo	4300
mwj		Maligo	4301
mwk		Kita Maninkakan	4302
mwl		Mirandese	4303
mwm		Sar	4304
mwn		Nyamwanga	4305
mwo		Central Maewo	4306
mwp		Kala Lagaw Ya	4307
mwq		Mün Chin	4308
mwr		Marwari	4309
mws		Mwimbi-Muthambi	4310
mwt		Moken	4311
mwu		Mittu	4312
mwv		Mentawai	4313
mww		Hmong Daw	4314
mwx		Mediak	4315
mwy		Mosiro	4316
mwz		Moingi	4317
mxa		Northwest Oaxaca Mixtec	4318
mxb		Tezoatlán Mixtec	4319
mxc		Manyika	4320
mxd		Modang	4321
mxe		Mele-Fila	4322
mxf		Malgbe	4323
mxg		Mbangala	4324
mxh		Mvuba	4325
mxi		Mozarabic	4326
mxj		Miju-Mishmi	4327
mxk		Monumbo	4328
mxl		Maxi Gbe	4329
mxm		Meramera	4330
mxn		Moi (Indonesia)	4331
mxo		Mbowe	4332
mxp		Tlahuitoltepec Mixe	4333
mxq		Juquila Mixe	4334
mxr		Murik (Malaysia)	4335
mxs		Huitepec Mixtec	4336
mxt		Jamiltepec Mixtec	4337
mxu		Mada (Cameroon)	4338
mxv		Metlatónoc Mixtec	4339
mxw		Namo	4340
mxx		Mahou	4341
mxy		Southeastern Nochixtlán Mixtec	4342
mxz		Central Masela	4343
mya	my	Burmese	4344
myb		Mbay	4345
myc		Mayeka	4346
myd		Maramba	4347
mye		Myene	4348
myf		Bambassi	4349
myg		Manta	4350
myh		Makah	4351
myi		Mina (India)	4352
myj		Mangayat	4353
myk		Mamara Senoufo	4354
myl		Moma	4355
mym		Me'en	4356
myo		Anfillo	4357
myp		Pirahã	4358
myq		Forest Maninka	4359
myr		Muniche	4360
mys		Mesmes	4361
myu		Mundurukú	4362
myv		Erzya	4363
myw		Muyuw	4364
myx		Masaaba	4365
myy		Macuna	4366
myz		Classical Mandaic	4367
mza		Santa María Zacatepec Mixtec	4368
mzb		Tumzabt	4369
mzc		Madagascar Sign Language	4370
mzd		Malimba	4371
mze		Morawa	4372
mzg		Monastic Sign Language	4373
mzh		Wichí Lhamtés Güisnay	4374
mzi		Ixcatlán Mazatec	4375
mzj		Manya	4376
mzk		Nigeria Mambila	4377
mzl		Mazatlán Mixe	4378
mzm		Mumuye	4379
mzn		Mazanderani	4380
mzo		Matipuhy	4381
mzp		Movima	4382
mzq		Mori Atas	4383
mzr		Marúbo	4384
mzs		Macanese	4385
mzt		Mintil	4386
mzu		Inapang	4387
mzv		Manza	4388
mzw		Deg	4389
mzx		Mawayana	4390
mzy		Mozambican Sign Language	4391
mzz		Maiadomu	4392
naa		Namla	4393
nab		Southern Nambikuára	4394
nac		Narak	4395
nad		Nijadali	4396
nae		Naka'ela	4397
naf		Nabak	4398
nag		Naga Pidgin	4399
naj		Nalu	4400
nak		Nakanai	4401
nal		Nalik	4402
nam		Nangikurrunggurr	4403
nan		Min Nan Chinese	4404
nao		Naaba	4405
nap		Neapolitan	4406
naq		Nama (Namibia)	4407
nar		Iguta	4408
nas		Naasioi	4409
nat		Hungworo	4410
nau	na	Nauru	4411
nav	nv	Navajo	4412
naw		Nawuri	4413
nax		Nakwi	4414
nay		Narrinyeri	4415
naz		Coatepec Nahuatl	4416
nba		Nyemba	4417
nbb		Ndoe	4418
nbc		Chang Naga	4419
nbd		Ngbinda	4420
nbe		Konyak Naga	4421
nbf		Naxi	4422
nbg		Nagarchal	4423
nbh		Ngamo	4424
nbi		Mao Naga	4425
nbj		Ngarinman	4426
nbk		Nake	4427
nbl	nr	South Ndebele	4428
nbm		Ngbaka Ma'bo	4429
nbn		Kuri	4430
nbo		Nkukoli	4431
nbp		Nnam	4432
nbq		Nggem	4433
nbr		Numana-Nunku-Gbantu-Numbu	4434
nbs		Namibian Sign Language	4435
nbt		Na	4436
nbu		Rongmei Naga	4437
nbv		Ngamambo	4438
nbw		Southern Ngbandi	4439
nbx		Ngura	4440
nby		Ningera	4441
nca		Iyo	4442
ncb		Central Nicobarese	4443
ncc		Ponam	4444
ncd		Nachering	4445
nce		Yale	4446
ncf		Notsi	4447
ncg		Nisga'a	4448
nch		Central Huasteca Nahuatl	4449
nci		Classical Nahuatl	4450
ncj		Northern Puebla Nahuatl	4451
nck		Nakara	4452
ncl		Michoacán Nahuatl	4453
ncm		Nambo	4454
ncn		Nauna	4455
nco		Sibe	4456
ncp		Ndaktup	4457
ncr		Ncane	4458
ncs		Nicaraguan Sign Language	4459
nct		Chothe Naga	4460
ncu		Chumburung	4461
ncx		Central Puebla Nahuatl	4462
ncz		Natchez	4463
nda		Ndasa	4464
ndb		Kenswei Nsei	4465
ndc		Ndau	4466
ndd		Nde-Nsele-Nta	4467
nde	nd	North Ndebele	4468
ndf		Nadruvian	4469
ndg		Ndengereko	4470
ndh		Ndali	4471
ndi		Samba Leko	4472
ndj		Ndamba	4473
ndk		Ndaka	4474
ndl		Ndolo	4475
ndm		Ndam	4476
ndn		Ngundi	4477
ndo	ng	Ndonga	4478
ndp		Ndo	4479
ndq		Ndombe	4480
ndr		Ndoola	4481
nds		Low German	4482
ndt		Ndunga	4483
ndu		Dugun	4484
ndv		Ndut	4485
ndw		Ndobo	4486
ndx		Nduga	4487
ndy		Lutos	4488
ndz		Ndogo	4489
nea		Eastern Ngad'a	4490
neb		Toura (Côte d'Ivoire)	4491
nec		Nedebang	4492
ned		Nde-Gbite	4493
nee		Kumak	4494
nef		Nefamese	4495
neg		Negidal	4496
neh		Nyenkha	4497
nei		Neo-Hittite	4498
nej		Neko	4499
nek		Neku	4500
nem		Nemi	4501
nen		Nengone	4502
neo		Ná-Meo	4503
nep	ne	Nepali	4504
neq		North Central Mixe	4505
ner		Yahadian	4506
nes		Bhoti Kinnauri	4507
net		Nete	4508
nev		Nyaheun	4509
new		Newari	4510
nex		Neme	4511
ney		Neyo	4512
nez		Nez Perce	4513
nfa		Dhao	4514
nfd		Ahwai	4515
nfl		Ayiwo	4516
nfr		Nafaanra	4517
nfu		Mfumte	4518
nga		Ngbaka	4519
ngb		Northern Ngbandi	4520
ngc		Ngombe (Democratic Republic of Congo)	4521
ngd		Ngando (Central African Republic)	4522
nge		Ngemba	4523
ngg		Ngbaka Manza	4524
ngh		N/u	4525
ngi		Ngizim	4526
ngj		Ngie	4527
ngk		Ngalkbun	4528
ngl		Lomwe	4529
ngm		Ngatik Men's Creole	4530
ngn		Ngwo	4531
ngo		Ngoni	4532
ngp		Ngulu	4533
ngq		Ngurimi	4534
ngr		Nanggu	4535
ngs		Gvoko	4536
ngt		Ngeq	4537
ngu		Guerrero Nahuatl	4538
ngv		Nagumi	4539
ngw		Ngwaba	4540
ngx		Nggwahyi	4541
ngy		Tibea	4542
ngz		Ngungwel	4543
nha		Nhanda	4544
nhb		Beng	4545
nhc		Tabasco Nahuatl	4546
nhd		Chiripá	4547
nhe		Eastern Huasteca Nahuatl	4548
nhf		Nhuwala	4549
nhg		Tetelcingo Nahuatl	4550
nhh		Nahari	4551
nhi		Zacatlán-Ahuacatlán-Tepetzintla Nahuatl	4552
nhk		Isthmus-Cosoleacaque Nahuatl	4553
nhm		Morelos Nahuatl	4554
nhn		Central Nahuatl	4555
nho		Takuu	4556
nhp		Isthmus-Pajapan Nahuatl	4557
nhq		Huaxcaleca Nahuatl	4558
nhr		Naro	4559
nht		Ometepec Nahuatl	4560
nhu		Noone	4561
nhv		Temascaltepec Nahuatl	4562
nhw		Western Huasteca Nahuatl	4563
nhx		Isthmus-Mecayapan Nahuatl	4564
nhy		Northern Oaxaca Nahuatl	4565
nhz		Santa María La Alta Nahuatl	4566
nia		Nias	4567
nib		Nakama	4568
nid		Ngandi	4569
nie		Niellim	4570
nif		Nek	4571
nig		Ngalakan	4572
nih		Nyiha (Tanzania)	4573
nii		Nii	4574
nij		Ngaju	4575
nik		Southern Nicobarese	4576
nil		Nila	4577
nim		Nilamba	4578
nin		Ninzo	4579
nio		Nganasan	4580
niq		Nandi	4581
nir		Nimboran	4582
nis		Nimi	4583
nit		Southeastern Kolami	4584
niu		Niuean	4585
niv		Gilyak	4586
niw		Nimo	4587
nix		Hema	4588
niy		Ngiti	4589
niz		Ningil	4590
nja		Nzanyi	4591
njb		Nocte Naga	4592
njd		Ndonde Hamba	4593
njh		Lotha Naga	4594
nji		Gudanji	4595
njj		Njen	4596
njl		Njalgulgule	4597
njm		Angami Naga	4598
njn		Liangmai Naga	4599
njo		Ao Naga	4600
njr		Njerep	4601
njs		Nisa	4602
njt		Ndyuka-Trio Pidgin	4603
nju		Ngadjunmaya	4604
njx		Kunyi	4605
njy		Njyem	4606
nka		Nkoya	4607
nkb		Khoibu Naga	4608
nkc		Nkongho	4609
nkd		Koireng	4610
nke		Duke	4611
nkf		Inpui Naga	4612
nkg		Nekgini	4613
nkh		Khezha Naga	4614
nki		Thangal Naga	4615
nkj		Nakai	4616
nkk		Nokuku	4617
nkm		Namat	4618
nkn		Nkangala	4619
nko		Nkonya	4620
nkp		Niuatoputapu	4621
nkq		Nkami	4622
nkr		Nukuoro	4623
nks		North Asmat	4624
nkt		Nyika (Tanzania)	4625
nku		Bouna Kulango	4626
nkv		Nyika (Malawi and Zambia)	4627
nkw		Nkutu	4628
nkx		Nkoroo	4629
nkz		Nkari	4630
nla		Ngombale	4631
nlc		Nalca	4632
nld	nl	Dutch	4633
nle		East Nyala	4634
nlg		Gela	4635
nli		Grangali	4636
nlj		Nyali	4637
nlk		Ninia Yali	4638
nll		Nihali	4639
nln		Durango Nahuatl	4640
nlo		Ngul	4641
nlr		Ngarla	4642
nlu		Nchumbulu	4643
nlv		Orizaba Nahuatl	4644
nlx		Nahali	4645
nly		Nyamal	4646
nlz		Nalögo	4647
nma		Maram Naga	4648
nmb		Big Nambas	4649
nmc		Ngam	4650
nmd		Ndumu	4651
nme		Mzieme Naga	4652
nmf		Tangkhul Naga	4653
nmg		Kwasio	4654
nmh		Monsang Naga	4655
nmi		Nyam	4656
nmj		Ngombe (Central African Republic)	4657
nmk		Namakura	4658
nml		Ndemli	4659
nmm		Manangba	4660
nmn		!Xóõ	4661
nmo		Moyon Naga	4662
nmp		Nimanbur	4663
nmq		Nambya	4664
nmr		Nimbari	4665
nms		Letemboi	4666
nmt		Namonuito	4667
nmu		Northeast Maidu	4668
nmv		Ngamini	4669
nmw		Nimoa	4670
nmx		Nama (Papua New Guinea)	4671
nmy		Namuyi	4672
nmz		Nawdm	4673
nna		Nyangumarta	4674
nnb		Nande	4675
nnc		Nancere	4676
nnd		West Ambae	4677
nne		Ngandyera	4678
nnf		Ngaing	4679
nng		Maring Naga	4680
nnh		Ngiemboon	4681
nni		North Nuaulu	4682
nnj		Nyangatom	4683
nnk		Nankina	4684
nnl		Northern Rengma Naga	4685
nnm		Namia	4686
nnn		Ngete	4687
nno	nn	Norwegian Nynorsk	4688
nnp		Wancho Naga	4689
nnq		Ngindo	4690
nnr		Narungga	4691
nns		Ningye	4692
nnt		Nanticoke	4693
nnu		Dwang	4694
nnv		Nugunu (Australia)	4695
nnw		Southern Nuni	4696
nnx		Ngong	4697
nny		Nyangga	4698
nnz		Nda'nda'	4699
noa		Woun Meu	4700
nob	nb	Norwegian Bokmål	4701
noc		Nuk	4702
nod		Northern Thai	4703
noe		Nimadi	4704
nof		Nomane	4705
nog		Nogai	4706
noh		Nomu	4707
noi		Noiri	4708
noj		Nonuya	4709
nok		Nooksack	4710
nom		Nocamán	4711
non		Old Norse	4712
noo		Nootka	4713
nop		Numanggang	4714
noq		Ngongo	4715
nor	no	Norwegian	4716
nos		Eastern Nisu	4717
not		Nomatsiguenga	4718
nou		Ewage-Notu	4719
nov		Novial	4720
now		Nyambo	4721
noy		Noy	4722
noz		Nayi	4723
npa		Nar Phu	4724
npb		Nupbikha	4725
nph		Phom Naga	4726
npl		Southeastern Puebla Nahuatl	4727
npn		Mondropolon	4728
npo		Pochuri Naga	4729
nps		Nipsan	4730
npu		Puimei Naga	4731
npy		Napu	4732
nqg		Southern Nago	4733
nqk		Kura Ede Nago	4734
nqm		Ndom	4735
nqn		Nen	4736
nqo		N'Ko	4737
nra		Ngom	4738
nrb		Nara	4739
nrc		Noric	4740
nre		Southern Rengma Naga	4741
nrg		Narango	4742
nri		Chokri Naga	4743
nrl		Ngarluma	4744
nrm		Narom	4745
nrn		Norn	4746
nrp		North Picene	4747
nrr		Norra	4748
nrt		Northern Kalapuya	4749
nrx		Ngurmbur	4750
nrz		Lala	4751
nsa		Sangtam Naga	4752
nsc		Nshi	4753
nsd		Southern Nisu	4754
nse		Nsenga	4755
nsg		Ngasa	4756
nsh		Ngoshie	4757
nsi		Nigerian Sign Language	4758
nsk		Naskapi	4759
nsl		Norwegian Sign Language	4760
nsm		Sumi Naga	4761
nsn		Nehan	4762
nso		Pedi	4763
nsp		Nepalese Sign Language	4764
nsq		Northern Sierra Miwok	4765
nsr		Maritime Sign Language	4766
nss		Nali	4767
nst		Tase Naga	4768
nsu		Sierra Negra Nahuatl	4769
nsv		Southwestern Nisu	4770
nsw		Navut	4771
nsx		Nsongo	4772
nsy		Nasal	4773
nsz		Nisenan	4774
nte		Nathembo	4775
nti		Natioro	4776
ntj		Ngaanyatjarra	4777
ntk		Ikoma-Nata-Isenye	4778
ntm		Nateni	4779
nto		Ntomba	4780
ntp		Northern Tepehuan	4781
ntr		Delo	4782
nts		Natagaimas	4783
ntu		Natügu	4784
ntw		Nottoway	4785
nty		Mantsi	4786
ntz		Natanzi	4787
nua		Yuaga	4788
nuc		Nukuini	4789
nud		Ngala	4790
nue		Ngundu	4791
nuf		Nusu	4792
nug		Nungali	4793
nuh		Ndunda	4794
nui		Ngumbi	4795
nuj		Nyole	4796
nul		Nusa Laut	4797
num		Niuafo'ou	4798
nun		Nung (Myanmar)	4799
nuo		Nguôn	4800
nup		Nupe-Nupe-Tako	4801
nuq		Nukumanu	4802
nur		Nukuria	4803
nus		Nuer	4804
nut		Nung (Viet Nam)	4805
nuu		Ngbundu	4806
nuv		Northern Nuni	4807
nuw		Nguluwan	4808
nux		Mehek	4809
nuy		Nunggubuyu	4810
nuz		Tlamacazapa Nahuatl	4811
nvh		Nasarian	4812
nvm		Namiae	4813
nwa		Nawathinehena	4814
nwb		Nyabwa	4815
nwc		Classical Newari	4816
nwe		Ngwe	4817
nwi		Southwest Tanna	4818
nwm		Nyamusa-Molo	4819
nwr		Nawaru	4820
nwx		Middle Newar	4821
nwy		Nottoway-Meherrin	4822
nxa		Nauete	4823
nxd		Ngando (Democratic Republic of Congo)	4824
nxe		Nage	4825
nxg		Ngad'a	4826
nxi		Nindi	4827
nxl		South Nuaulu	4828
nxm		Numidian	4829
nxn		Ngawun	4830
nxr		Ninggerum	4831
nxu		Narau	4832
nxx		Nafri	4833
nya	ny	Nyanja	4834
nyb		Nyangbo	4835
nyc		Nyanga-li	4836
nyd		Nyore	4837
nye		Nyengo	4838
nyf		Giryama	4839
nyg		Nyindu	4840
nyh		Nyigina	4841
nyi		Ama (Sudan)	4842
nyj		Nyanga	4843
nyk		Nyaneka	4844
nyl		Nyeu	4845
nym		Nyamwezi	4846
nyn		Nyankole	4847
nyo		Nyoro	4848
nyp		Nyang'i	4849
nyq		Nayini	4850
nyr		Nyiha (Malawi)	4851
nys		Nyunga	4852
nyt		Nyawaygi	4853
nyu		Nyungwe	4854
nyv		Nyulnyul	4855
nyw		Nyaw	4856
nyx		Nganyaywana	4857
nyy		Nyakyusa-Ngonde	4858
nza		Tigon Mbembe	4859
nzb		Njebi	4860
nzi		Nzima	4861
nzk		Nzakara	4862
nzm		Zeme Naga	4863
nzs		New Zealand Sign Language	4864
nzu		Teke-Nzikou	4865
nzy		Nzakambay	4866
nzz		Nanga Dama Dogon	4867
oaa		Orok	4868
oac		Oroch	4869
oar		Old Aramaic (up to 700 BCE)	4870
oav		Old Avar	4871
obi		Obispeño	4872
obk		Southern Bontok	4873
obl		Oblo	4874
obm		Moabite	4875
obo		Obo Manobo	4876
obr		Old Burmese	4877
obt		Old Breton	4878
obu		Obulom	4879
oca		Ocaina	4880
och		Old Chinese	4881
oci	oc	Occitan (post 1500)	4882
oco		Old Cornish	4883
ocu		Atzingo Matlatzinca	4884
oda		Odut	4885
odk		Od	4886
odt		Old Dutch	4887
odu		Odual	4888
ofo		Ofo	4889
ofs		Old Frisian	4890
ofu		Efutop	4891
ogb		Ogbia	4892
ogc		Ogbah	4893
oge		Old Georgian	4894
ogg		Ogbogolo	4895
ogo		Khana	4896
ogu		Ogbronuagum	4897
oht		Old Hittite	4898
ohu		Old Hungarian	4899
oia		Oirata	4900
oin		Inebu One	4901
ojb		Northwestern Ojibwa	4902
ojc		Central Ojibwa	4903
ojg		Eastern Ojibwa	4904
oji	oj	Ojibwa	4905
ojp		Old Japanese	4906
ojs		Severn Ojibwa	4907
ojv		Ontong Java	4908
ojw		Western Ojibwa	4909
oka		Okanagan	4910
okb		Okobo	4911
okd		Okodia	4912
oke		Okpe (Southwestern Edo)	4913
okh		Koresh-e Rostam	4914
oki		Okiek	4915
okj		Oko-Juwoi	4916
okk		Kwamtim One	4917
okl		Old Kentish Sign Language	4918
okm		Middle Korean (10th-16th cent.)	4919
okn		Oki-No-Erabu	4920
oko		Old Korean (3rd-9th cent.)	4921
okr		Kirike	4922
oks		Oko-Eni-Osayen	4923
oku		Oku	4924
okv		Orokaiva	4925
okx		Okpe (Northwestern Edo)	4926
ola		Walungge	4927
old		Mochi	4928
ole		Olekha	4929
olm		Oloma	4930
olo		Livvi	4931
olr		Olrat	4932
oma		Omaha-Ponca	4933
omb		East Ambae	4934
omc		Mochica	4935
ome		Omejes	4936
omg		Omagua	4937
omi		Omi	4938
omk		Omok	4939
oml		Ombo	4940
omn		Minoan	4941
omo		Utarmbung	4942
omp		Old Manipuri	4943
omr		Old Marathi	4944
omt		Omotik	4945
omu		Omurano	4946
omw		South Tairora	4947
omx		Old Mon	4948
ona		Ona	4949
onb		Lingao	4950
one		Oneida	4951
ong		Olo	4952
oni		Onin	4953
onj		Onjob	4954
onk		Kabore One	4955
onn		Onobasulu	4956
ono		Onondaga	4957
onp		Sartang	4958
onr		Northern One	4959
ons		Ono	4960
ont		Ontenu	4961
onu		Unua	4962
onw		Old Nubian	4963
onx		Onin Based Pidgin	4964
ood		Tohono O'odham	4965
oog		Ong	4966
oon		Önge	4967
oor		Oorlams	4968
oos		Old Ossetic	4969
opa		Okpamheri	4970
opk		Kopkaka	4971
opm		Oksapmin	4972
opo		Opao	4973
opt		Opata	4974
opy		Ofayé	4975
ora		Oroha	4976
orc		Orma	4977
ore		Orejón	4978
org		Oring	4979
orh		Oroqen	4980
ori	or	Oriya	4981
orm	om	Oromo	4982
orn		Orang Kanaq	4983
oro		Orokolo	4984
orr		Oruma	4985
ors		Orang Seletar	4986
ort		Adivasi Oriya	4987
oru		Ormuri	4988
orv		Old Russian	4989
orw		Oro Win	4990
orx		Oro	4991
orz		Ormu	4992
osa		Osage	4993
osc		Oscan	4994
osi		Osing	4995
oso		Ososo	4996
osp		Old Spanish	4997
oss	os	Ossetian	4998
ost		Osatu	4999
osu		Southern One	5000
osx		Old Saxon	5001
ota		Ottoman Turkish (1500-1928)	5002
otb		Old Tibetan	5003
otd		Ot Danum	5004
ote		Mezquital Otomi	5005
oti		Oti	5006
otk		Old Turkish	5007
otl		Tilapa Otomi	5008
otm		Eastern Highland Otomi	5009
otn		Tenango Otomi	5010
otq		Querétaro Otomi	5011
otr		Otoro	5012
ots		Estado de México Otomi	5013
ott		Temoaya Otomi	5014
otu		Otuke	5015
otw		Ottawa	5016
otx		Texcatepec Otomi	5017
oty		Old Tamil	5018
otz		Ixtenco Otomi	5019
oua		Tagargrent	5020
oub		Glio-Oubi	5021
oue		Ounge	5022
oui		Old Uighur	5023
oum		Ouma	5024
oun		!O!ung	5025
owi		Owiniga	5026
owl		Old Welsh	5027
oyb		Oy	5028
oyd		Oyda	5029
oym		Wayampi	5030
oyy		Oya'oya	5031
ozm		Koonzime	5032
pab		Parecís	5033
pac		Pacoh	5034
pad		Paumarí	5035
pae		Pagibete	5036
paf		Paranawát	5037
pag		Pangasinan	5038
pah		Tenharim	5039
pai		Pe	5040
pak		Parakanã	5041
pal		Pahlavi	5042
pam		Pampanga	5043
pan	pa	Panjabi	5044
pao		Northern Paiute	5045
pap		Papiamento	5046
paq		Parya	5047
par		Panamint	5048
pas		Papasena	5049
pat		Papitalai	5050
pau		Palauan	5051
pav		Pakaásnovos	5052
paw		Pawnee	5053
pax		Pankararé	5054
pay		Pech	5055
paz		Pankararú	5056
pbb		Páez	5057
pbc		Patamona	5058
pbe		Mezontla Popoloca	5059
pbf		Coyotepec Popoloca	5060
pbg		Paraujano	5061
pbh		E'ñapa Woromaipu	5062
pbi		Parkwa	5063
pbl		Mak (Nigeria)	5064
pbn		Kpasam	5065
pbo		Papel	5066
pbp		Badyara	5067
pbr		Pangwa	5068
pbs		Central Pame	5069
pbt		Southern Pashto	5070
pbu		Northern Pashto	5071
pbv		Pnar	5072
pby		Pyu	5073
pbz		Palu	5074
pca		Santa Inés Ahuatempan Popoloca	5075
pcb		Pear	5076
pcc		Bouyei	5077
pcd		Picard	5078
pce		Ruching Palaung	5079
pcf		Paliyan	5080
pcg		Paniya	5081
pch		Pardhan	5082
pci		Duruwa	5083
pcj		Parenga	5084
pck		Paite Chin	5085
pcl		Pardhi	5086
pcm		Nigerian Pidgin	5087
pcn		Piti	5088
pcp		Pacahuara	5089
pcr		Panang	5090
pcw		Pyapun	5091
pda		Anam	5092
pdc		Pennsylvania German	5093
pdi		Pa Di	5094
pdn		Podena	5095
pdo		Padoe	5096
pdt		Plautdietsch	5097
pdu		Kayan	5098
pea		Peranakan Indonesian	5099
peb		Eastern Pomo	5100
ped		Mala (Papua New Guinea)	5101
pee		Taje	5102
pef		Northeastern Pomo	5103
peg		Pengo	5104
peh		Bonan	5105
pei		Chichimeca-Jonaz	5106
pej		Northern Pomo	5107
pek		Penchal	5108
pel		Pekal	5109
pem		Phende	5110
peo		Old Persian (ca. 600-400 B.C.)	5111
pep		Kunja	5112
peq		Southern Pomo	5113
pes		Iranian Persian	5114
pev		Pémono	5115
pex		Petats	5116
pey		Petjo	5117
pez		Eastern Penan	5118
pfa		Pááfang	5119
pfe		Peere	5120
pfl		Pfaelzisch	5121
pga		Sudanese Creole Arabic	5122
pgg		Pangwali	5123
pgi		Pagi	5124
pgk		Rerep	5125
pgn		Paelignian	5126
pgs		Pangseng	5127
pgu		Pagu	5128
pgy		Pongyong	5129
pha		Pa-Hng	5130
phd		Phudagi	5131
phg		Phuong	5132
phh		Phukha	5133
phk		Phake	5134
phl		Phalura	5135
phm		Phimbi	5136
phn		Phoenician	5137
pho		Phunoi	5138
phq		Phana'	5139
phr		Pahari-Potwari	5140
pht		Phu Thai	5141
phu		Phuan	5142
phv		Pahlavani	5143
phw		Phangduwali	5144
pia		Pima Bajo	5145
pib		Yine	5146
pic		Pinji	5147
pid		Piaroa	5148
pie		Piro	5149
pif		Pingelapese	5150
pig		Pisabo	5151
pih		Pitcairn-Norfolk	5152
pii		Pini	5153
pij		Pijao	5154
pil		Yom	5155
pim		Powhatan	5156
pin		Piame	5157
pio		Piapoco	5158
pip		Pero	5159
pir		Piratapuyo	5160
pis		Pijin	5161
pit		Pitta Pitta	5162
piu		Pintupi-Luritja	5163
piv		Pileni	5164
piw		Pimbwe	5165
pix		Piu	5166
piy		Piya-Kwonci	5167
piz		Pije	5168
pjt		Pitjantjatjara	5169
pka		Ardhamāgadhī Prākrit	5170
pkb		Pokomo	5171
pkc		Paekche	5172
pkg		Pak-Tong	5173
pkh		Pankhu	5174
pkn		Pakanha	5175
pko		Pökoot	5176
pkp		Pukapuka	5177
pkr		Attapady Kurumba	5178
pks		Pakistan Sign Language	5179
pkt		Maleng	5180
pku		Paku	5181
pla		Miani	5182
plb		Polonombauk	5183
plc		Central Palawano	5184
pld		Polari	5185
ple		Palu'e	5186
plg		Pilagá	5187
plh		Paulohi	5188
pli	pi	Pali	5189
plj		Polci	5190
plk		Kohistani Shina	5191
pll		Shwe Palaung	5192
pln		Palenquero	5193
plo		Oluta Popoluca	5194
plp		Palpa	5195
plq		Palaic	5196
plr		Palaka Senoufo	5197
pls		San Marcos Tlalcoyalco Popoloca	5198
plt		Plateau Malagasy	5199
plu		Palikúr	5200
plv		Southwest Palawano	5201
plw		Brooke's Point Palawano	5202
ply		Bolyu	5203
plz		Paluan	5204
pma		Paama	5205
pmb		Pambia	5206
pmc		Palumata	5207
pme		Pwaamei	5208
pmf		Pamona	5209
pmh		Māhārāṣṭri Prākrit	5210
pmi		Northern Pumi	5211
pmj		Southern Pumi	5212
pmk		Pamlico	5213
pml		Lingua Franca	5214
pmm		Pomo	5215
pmn		Pam	5216
pmo		Pom	5217
pmq		Northern Pame	5218
pmr		Paynamar	5219
pms		Piemontese	5220
pmt		Tuamotuan	5221
pmu		Mirpur Panjabi	5222
pmw		Plains Miwok	5223
pmx		Poumei Naga	5224
pmy		Papuan Malay	5225
pmz		Southern Pame	5226
pna		Punan Bah-Biau	5227
pnb		Western Panjabi	5228
pnc		Pannei	5229
pne		Western Penan	5230
png		Pongu	5231
pnh		Penrhyn	5232
pni		Aoheng	5233
pnm		Punan Batu 1	5234
pnn		Pinai-Hagahai	5235
pno		Panobo	5236
pnp		Pancana	5237
pnq		Pana (Burkina Faso)	5238
pnr		Panim	5239
pns		Ponosakan	5240
pnt		Pontic	5241
pnu		Jiongnai Bunu	5242
pnv		Pinigura	5243
pnw		Panytyima	5244
pnx		Phong-Kniang	5245
pny		Pinyin	5246
pnz		Pana (Central African Republic)	5247
poc		Poqomam	5248
pod		Ponares	5249
poe		San Juan Atzingo Popoloca	5250
pof		Poke	5251
pog		Potiguára	5252
poh		Poqomchi'	5253
poi		Highland Popoluca	5254
pok		Pokangá	5255
pol	pl	Polish	5256
pom		Southeastern Pomo	5257
pon		Pohnpeian	5258
poo		Central Pomo	5259
pop		Pwapwa	5260
poq		Texistepec Popoluca	5261
por	pt	Portuguese	5262
pos		Sayula Popoluca	5263
pot		Potawatomi	5264
pov		Upper Guinea Crioulo	5265
pow		San Felipe Otlaltepec Popoloca	5266
pox		Polabian	5267
poy		Pogolo	5268
ppa		Pao	5269
ppe		Papi	5270
ppi		Paipai	5271
ppk		Uma	5272
ppl		Pipil	5273
ppm		Papuma	5274
ppn		Papapana	5275
ppo		Folopa	5276
ppp		Pelende	5277
ppq		Pei	5278
ppr		Piru	5279
pps		San Luís Temalacayuca Popoloca	5280
ppt		Pare	5281
ppu		Papora	5282
pqa		Pa'a	5283
pqm		Malecite-Passamaquoddy	5284
prb		Lua'	5285
prc		Parachi	5286
prd		Parsi-Dari	5287
pre		Principense	5288
prf		Paranan	5289
prg		Prussian	5290
prh		Porohanon	5291
pri		Paicî	5292
prk		Parauk	5293
prl		Peruvian Sign Language	5294
prm		Kibiri	5295
prn		Prasuni	5296
pro		Old Provençal (to 1500)	5297
prp		Parsi	5298
prq		Ashéninka Perené	5299
prr		Puri	5300
prs		Dari	5301
prt		Phai	5302
pru		Puragi	5303
prw		Parawen	5304
prx		Purik	5305
pry		Pray 3	5306
prz		Providencia Sign Language	5307
psa		Asue Awyu	5308
psc		Persian Sign Language	5309
psd		Plains Indian Sign Language	5310
pse		Central Malay	5311
psg		Penang Sign Language	5312
psh		Southwest Pashayi	5313
psi		Southeast Pashayi	5314
psl		Puerto Rican Sign Language	5315
psm		Pauserna	5316
psn		Panasuan	5317
pso		Polish Sign Language	5318
psp		Philippine Sign Language	5319
psq		Pasi	5320
psr		Portuguese Sign Language	5321
pss		Kaulong	5322
pst		Central Pashto	5323
psu		Sauraseni Prākrit	5324
psw		Port Sandwich	5325
psy		Piscataway	5326
pta		Pai Tavytera	5327
pth		Pataxó Hã-Ha-Hãe	5328
pti		Pintiini	5329
ptn		Patani	5330
pto		Zo'é	5331
ptp		Patep	5332
ptr		Piamatsina	5333
ptt		Enrekang	5334
ptu		Bambam	5335
ptv		Port Vato	5336
ptw		Pentlatch	5337
pty		Pathiya	5338
pua		Western Highland Purepecha	5339
pub		Purum	5340
puc		Punan Merap	5341
pud		Punan Aput	5342
pue		Puelche	5343
puf		Punan Merah	5344
pug		Phuie	5345
pui		Puinave	5346
puj		Punan Tubu	5347
puk		Pu Ko	5348
pum		Puma	5349
puo		Puoc	5350
pup		Pulabu	5351
puq		Puquina	5352
pur		Puruborá	5353
pus	ps	Pushto	5354
put		Putoh	5355
puu		Punu	5356
puw		Puluwatese	5357
pux		Puare	5358
puy		Purisimeño	5359
puz		Purum Naga	5360
pwa		Pawaia	5361
pwb		Panawa	5362
pwg		Gapapaiwa	5363
pwm		Molbog	5364
pwn		Paiwan	5365
pwo		Pwo Western Karen	5366
pwr		Powari	5367
pww		Pwo Northern Karen	5368
pxm		Quetzaltepec Mixe	5369
pye		Pye Krumen	5370
pym		Fyam	5371
pyn		Poyanáwa	5372
pys		Paraguayan Sign Language	5373
pyu		Puyuma	5374
pyx		Pyu (Myanmar)	5375
pyy		Pyen	5376
pzn		Para Naga	5377
qaa		Reserved for local use	5378
qab		Reserved for local use	5379
qac		Reserved for local use	5380
qad		Reserved for local use	5381
qae		Reserved for local use	5382
qaf		Reserved for local use	5383
qag		Reserved for local use	5384
qah		Reserved for local use	5385
qai		Reserved for local use	5386
qaj		Reserved for local use	5387
qak		Reserved for local use	5388
qal		Reserved for local use	5389
qam		Reserved for local use	5390
qan		Reserved for local use	5391
qao		Reserved for local use	5392
qap		Reserved for local use	5393
qaq		Reserved for local use	5394
qar		Reserved for local use	5395
qas		Reserved for local use	5396
qat		Reserved for local use	5397
qau		Reserved for local use	5398
qav		Reserved for local use	5399
qaw		Reserved for local use	5400
qax		Reserved for local use	5401
qay		Reserved for local use	5402
qaz		Reserved for local use	5403
qba		Reserved for local use	5404
qbb		Reserved for local use	5405
qbc		Reserved for local use	5406
qbd		Reserved for local use	5407
qbe		Reserved for local use	5408
qbf		Reserved for local use	5409
qbg		Reserved for local use	5410
qbh		Reserved for local use	5411
qbi		Reserved for local use	5412
qbj		Reserved for local use	5413
qbk		Reserved for local use	5414
qbl		Reserved for local use	5415
qbm		Reserved for local use	5416
qbn		Reserved for local use	5417
qbo		Reserved for local use	5418
qbp		Reserved for local use	5419
qbq		Reserved for local use	5420
qbr		Reserved for local use	5421
qbs		Reserved for local use	5422
qbt		Reserved for local use	5423
qbu		Reserved for local use	5424
qbv		Reserved for local use	5425
qbw		Reserved for local use	5426
qbx		Reserved for local use	5427
qby		Reserved for local use	5428
qbz		Reserved for local use	5429
qca		Reserved for local use	5430
qcb		Reserved for local use	5431
qcc		Reserved for local use	5432
qcd		Reserved for local use	5433
qce		Reserved for local use	5434
qcf		Reserved for local use	5435
qcg		Reserved for local use	5436
qch		Reserved for local use	5437
qci		Reserved for local use	5438
qcj		Reserved for local use	5439
qck		Reserved for local use	5440
qcl		Reserved for local use	5441
qcm		Reserved for local use	5442
qcn		Reserved for local use	5443
qco		Reserved for local use	5444
qcp		Reserved for local use	5445
qcq		Reserved for local use	5446
qcr		Reserved for local use	5447
qcs		Reserved for local use	5448
qct		Reserved for local use	5449
qcu		Reserved for local use	5450
qcv		Reserved for local use	5451
qcw		Reserved for local use	5452
qcx		Reserved for local use	5453
qcy		Reserved for local use	5454
qcz		Reserved for local use	5455
qda		Reserved for local use	5456
qdb		Reserved for local use	5457
qdc		Reserved for local use	5458
qdd		Reserved for local use	5459
qde		Reserved for local use	5460
qdf		Reserved for local use	5461
qdg		Reserved for local use	5462
qdh		Reserved for local use	5463
qdi		Reserved for local use	5464
qdj		Reserved for local use	5465
qdk		Reserved for local use	5466
qdl		Reserved for local use	5467
qdm		Reserved for local use	5468
qdn		Reserved for local use	5469
qdo		Reserved for local use	5470
qdp		Reserved for local use	5471
qdq		Reserved for local use	5472
qdr		Reserved for local use	5473
qds		Reserved for local use	5474
qdt		Reserved for local use	5475
qdu		Reserved for local use	5476
qdv		Reserved for local use	5477
qdw		Reserved for local use	5478
qdx		Reserved for local use	5479
qdy		Reserved for local use	5480
qdz		Reserved for local use	5481
qea		Reserved for local use	5482
qeb		Reserved for local use	5483
qec		Reserved for local use	5484
qed		Reserved for local use	5485
qee		Reserved for local use	5486
qef		Reserved for local use	5487
qeg		Reserved for local use	5488
qeh		Reserved for local use	5489
qei		Reserved for local use	5490
qej		Reserved for local use	5491
qek		Reserved for local use	5492
qel		Reserved for local use	5493
qem		Reserved for local use	5494
qen		Reserved for local use	5495
qeo		Reserved for local use	5496
qep		Reserved for local use	5497
qeq		Reserved for local use	5498
qer		Reserved for local use	5499
qes		Reserved for local use	5500
qet		Reserved for local use	5501
qeu		Reserved for local use	5502
qev		Reserved for local use	5503
qew		Reserved for local use	5504
qex		Reserved for local use	5505
qey		Reserved for local use	5506
qez		Reserved for local use	5507
qfa		Reserved for local use	5508
qfb		Reserved for local use	5509
qfc		Reserved for local use	5510
qfd		Reserved for local use	5511
qfe		Reserved for local use	5512
qff		Reserved for local use	5513
qfg		Reserved for local use	5514
qfh		Reserved for local use	5515
qfi		Reserved for local use	5516
qfj		Reserved for local use	5517
qfk		Reserved for local use	5518
qfl		Reserved for local use	5519
qfm		Reserved for local use	5520
qfn		Reserved for local use	5521
qfo		Reserved for local use	5522
qfp		Reserved for local use	5523
qfq		Reserved for local use	5524
qfr		Reserved for local use	5525
qfs		Reserved for local use	5526
qft		Reserved for local use	5527
qfu		Reserved for local use	5528
qfv		Reserved for local use	5529
qfw		Reserved for local use	5530
qfx		Reserved for local use	5531
qfy		Reserved for local use	5532
qfz		Reserved for local use	5533
qga		Reserved for local use	5534
qgb		Reserved for local use	5535
qgc		Reserved for local use	5536
qgd		Reserved for local use	5537
qge		Reserved for local use	5538
qgf		Reserved for local use	5539
qgg		Reserved for local use	5540
qgh		Reserved for local use	5541
qgi		Reserved for local use	5542
qgj		Reserved for local use	5543
qgk		Reserved for local use	5544
qgl		Reserved for local use	5545
qgm		Reserved for local use	5546
qgn		Reserved for local use	5547
qgo		Reserved for local use	5548
qgp		Reserved for local use	5549
qgq		Reserved for local use	5550
qgr		Reserved for local use	5551
qgs		Reserved for local use	5552
qgt		Reserved for local use	5553
qgu		Reserved for local use	5554
qgv		Reserved for local use	5555
qgw		Reserved for local use	5556
qgx		Reserved for local use	5557
qgy		Reserved for local use	5558
qgz		Reserved for local use	5559
qha		Reserved for local use	5560
qhb		Reserved for local use	5561
qhc		Reserved for local use	5562
qhd		Reserved for local use	5563
qhe		Reserved for local use	5564
qhf		Reserved for local use	5565
qhg		Reserved for local use	5566
qhh		Reserved for local use	5567
qhi		Reserved for local use	5568
qhj		Reserved for local use	5569
qhk		Reserved for local use	5570
qhl		Reserved for local use	5571
qhm		Reserved for local use	5572
qhn		Reserved for local use	5573
qho		Reserved for local use	5574
qhp		Reserved for local use	5575
qhq		Reserved for local use	5576
qhr		Reserved for local use	5577
qhs		Reserved for local use	5578
qht		Reserved for local use	5579
qhu		Reserved for local use	5580
qhv		Reserved for local use	5581
qhw		Reserved for local use	5582
qhx		Reserved for local use	5583
qhy		Reserved for local use	5584
qhz		Reserved for local use	5585
qia		Reserved for local use	5586
qib		Reserved for local use	5587
qic		Reserved for local use	5588
qid		Reserved for local use	5589
qie		Reserved for local use	5590
qif		Reserved for local use	5591
qig		Reserved for local use	5592
qih		Reserved for local use	5593
qii		Reserved for local use	5594
qij		Reserved for local use	5595
qik		Reserved for local use	5596
qil		Reserved for local use	5597
qim		Reserved for local use	5598
qin		Reserved for local use	5599
qio		Reserved for local use	5600
qip		Reserved for local use	5601
qiq		Reserved for local use	5602
qir		Reserved for local use	5603
qis		Reserved for local use	5604
qit		Reserved for local use	5605
qiu		Reserved for local use	5606
qiv		Reserved for local use	5607
qiw		Reserved for local use	5608
qix		Reserved for local use	5609
qiy		Reserved for local use	5610
qiz		Reserved for local use	5611
qja		Reserved for local use	5612
qjb		Reserved for local use	5613
qjc		Reserved for local use	5614
qjd		Reserved for local use	5615
qje		Reserved for local use	5616
qjf		Reserved for local use	5617
qjg		Reserved for local use	5618
qjh		Reserved for local use	5619
qji		Reserved for local use	5620
qjj		Reserved for local use	5621
qjk		Reserved for local use	5622
qjl		Reserved for local use	5623
qjm		Reserved for local use	5624
qjn		Reserved for local use	5625
qjo		Reserved for local use	5626
qjp		Reserved for local use	5627
qjq		Reserved for local use	5628
qjr		Reserved for local use	5629
qjs		Reserved for local use	5630
qjt		Reserved for local use	5631
qju		Reserved for local use	5632
qjv		Reserved for local use	5633
qjw		Reserved for local use	5634
qjx		Reserved for local use	5635
qjy		Reserved for local use	5636
qjz		Reserved for local use	5637
qka		Reserved for local use	5638
qkb		Reserved for local use	5639
qkc		Reserved for local use	5640
qkd		Reserved for local use	5641
qke		Reserved for local use	5642
qkf		Reserved for local use	5643
qkg		Reserved for local use	5644
qkh		Reserved for local use	5645
qki		Reserved for local use	5646
qkj		Reserved for local use	5647
qkk		Reserved for local use	5648
qkl		Reserved for local use	5649
qkm		Reserved for local use	5650
qkn		Reserved for local use	5651
qko		Reserved for local use	5652
qkp		Reserved for local use	5653
qkq		Reserved for local use	5654
qkr		Reserved for local use	5655
qks		Reserved for local use	5656
qkt		Reserved for local use	5657
qku		Reserved for local use	5658
qkv		Reserved for local use	5659
qkw		Reserved for local use	5660
qkx		Reserved for local use	5661
qky		Reserved for local use	5662
qkz		Reserved for local use	5663
qla		Reserved for local use	5664
qlb		Reserved for local use	5665
qlc		Reserved for local use	5666
qld		Reserved for local use	5667
qle		Reserved for local use	5668
qlf		Reserved for local use	5669
qlg		Reserved for local use	5670
qlh		Reserved for local use	5671
qli		Reserved for local use	5672
qlj		Reserved for local use	5673
qlk		Reserved for local use	5674
qll		Reserved for local use	5675
qlm		Reserved for local use	5676
qln		Reserved for local use	5677
qlo		Reserved for local use	5678
qlp		Reserved for local use	5679
qlq		Reserved for local use	5680
qlr		Reserved for local use	5681
qls		Reserved for local use	5682
qlt		Reserved for local use	5683
qlu		Reserved for local use	5684
qlv		Reserved for local use	5685
qlw		Reserved for local use	5686
qlx		Reserved for local use	5687
qly		Reserved for local use	5688
qlz		Reserved for local use	5689
qma		Reserved for local use	5690
qmb		Reserved for local use	5691
qmc		Reserved for local use	5692
qmd		Reserved for local use	5693
qme		Reserved for local use	5694
qmf		Reserved for local use	5695
qmg		Reserved for local use	5696
qmh		Reserved for local use	5697
qmi		Reserved for local use	5698
qmj		Reserved for local use	5699
qmk		Reserved for local use	5700
qml		Reserved for local use	5701
qmm		Reserved for local use	5702
qmn		Reserved for local use	5703
qmo		Reserved for local use	5704
qmp		Reserved for local use	5705
qmq		Reserved for local use	5706
qmr		Reserved for local use	5707
qms		Reserved for local use	5708
qmt		Reserved for local use	5709
qmu		Reserved for local use	5710
qmv		Reserved for local use	5711
qmw		Reserved for local use	5712
qmx		Reserved for local use	5713
qmy		Reserved for local use	5714
qmz		Reserved for local use	5715
qna		Reserved for local use	5716
qnb		Reserved for local use	5717
qnc		Reserved for local use	5718
qnd		Reserved for local use	5719
qne		Reserved for local use	5720
qnf		Reserved for local use	5721
qng		Reserved for local use	5722
qnh		Reserved for local use	5723
qni		Reserved for local use	5724
qnj		Reserved for local use	5725
qnk		Reserved for local use	5726
qnl		Reserved for local use	5727
qnm		Reserved for local use	5728
qnn		Reserved for local use	5729
qno		Reserved for local use	5730
qnp		Reserved for local use	5731
qnq		Reserved for local use	5732
qnr		Reserved for local use	5733
qns		Reserved for local use	5734
qnt		Reserved for local use	5735
qnu		Reserved for local use	5736
qnv		Reserved for local use	5737
qnw		Reserved for local use	5738
qnx		Reserved for local use	5739
qny		Reserved for local use	5740
qnz		Reserved for local use	5741
qoa		Reserved for local use	5742
qob		Reserved for local use	5743
qoc		Reserved for local use	5744
qod		Reserved for local use	5745
qoe		Reserved for local use	5746
qof		Reserved for local use	5747
qog		Reserved for local use	5748
qoh		Reserved for local use	5749
qoi		Reserved for local use	5750
qoj		Reserved for local use	5751
qok		Reserved for local use	5752
qol		Reserved for local use	5753
qom		Reserved for local use	5754
qon		Reserved for local use	5755
qoo		Reserved for local use	5756
qop		Reserved for local use	5757
qoq		Reserved for local use	5758
qor		Reserved for local use	5759
qos		Reserved for local use	5760
qot		Reserved for local use	5761
qou		Reserved for local use	5762
qov		Reserved for local use	5763
qow		Reserved for local use	5764
qox		Reserved for local use	5765
qoy		Reserved for local use	5766
qoz		Reserved for local use	5767
qpa		Reserved for local use	5768
qpb		Reserved for local use	5769
qpc		Reserved for local use	5770
qpd		Reserved for local use	5771
qpe		Reserved for local use	5772
qpf		Reserved for local use	5773
qpg		Reserved for local use	5774
qph		Reserved for local use	5775
qpi		Reserved for local use	5776
qpj		Reserved for local use	5777
qpk		Reserved for local use	5778
qpl		Reserved for local use	5779
qpm		Reserved for local use	5780
qpn		Reserved for local use	5781
qpo		Reserved for local use	5782
qpp		Reserved for local use	5783
qpq		Reserved for local use	5784
qpr		Reserved for local use	5785
qps		Reserved for local use	5786
qpt		Reserved for local use	5787
qpu		Reserved for local use	5788
qpv		Reserved for local use	5789
qpw		Reserved for local use	5790
qpx		Reserved for local use	5791
qpy		Reserved for local use	5792
qpz		Reserved for local use	5793
qqa		Reserved for local use	5794
qqb		Reserved for local use	5795
qqc		Reserved for local use	5796
qqd		Reserved for local use	5797
qqe		Reserved for local use	5798
qqf		Reserved for local use	5799
qqg		Reserved for local use	5800
qqh		Reserved for local use	5801
qqi		Reserved for local use	5802
qqj		Reserved for local use	5803
qqk		Reserved for local use	5804
qql		Reserved for local use	5805
qqm		Reserved for local use	5806
qqn		Reserved for local use	5807
qqo		Reserved for local use	5808
qqp		Reserved for local use	5809
qqq		Reserved for local use	5810
qqr		Reserved for local use	5811
qqs		Reserved for local use	5812
qqt		Reserved for local use	5813
qqu		Reserved for local use	5814
qqv		Reserved for local use	5815
qqw		Reserved for local use	5816
qqx		Reserved for local use	5817
qqy		Reserved for local use	5818
qqz		Reserved for local use	5819
qra		Reserved for local use	5820
qrb		Reserved for local use	5821
qrc		Reserved for local use	5822
qrd		Reserved for local use	5823
qre		Reserved for local use	5824
qrf		Reserved for local use	5825
qrg		Reserved for local use	5826
qrh		Reserved for local use	5827
qri		Reserved for local use	5828
qrj		Reserved for local use	5829
qrk		Reserved for local use	5830
qrl		Reserved for local use	5831
qrm		Reserved for local use	5832
qrn		Reserved for local use	5833
qro		Reserved for local use	5834
qrp		Reserved for local use	5835
qrq		Reserved for local use	5836
qrr		Reserved for local use	5837
qrs		Reserved for local use	5838
qrt		Reserved for local use	5839
qru		Reserved for local use	5840
qrv		Reserved for local use	5841
qrw		Reserved for local use	5842
qrx		Reserved for local use	5843
qry		Reserved for local use	5844
qrz		Reserved for local use	5845
qsa		Reserved for local use	5846
qsb		Reserved for local use	5847
qsc		Reserved for local use	5848
qsd		Reserved for local use	5849
qse		Reserved for local use	5850
qsf		Reserved for local use	5851
qsg		Reserved for local use	5852
qsh		Reserved for local use	5853
qsi		Reserved for local use	5854
qsj		Reserved for local use	5855
qsk		Reserved for local use	5856
qsl		Reserved for local use	5857
qsm		Reserved for local use	5858
qsn		Reserved for local use	5859
qso		Reserved for local use	5860
qsp		Reserved for local use	5861
qsq		Reserved for local use	5862
qsr		Reserved for local use	5863
qss		Reserved for local use	5864
qst		Reserved for local use	5865
qsu		Reserved for local use	5866
qsv		Reserved for local use	5867
qsw		Reserved for local use	5868
qsx		Reserved for local use	5869
qsy		Reserved for local use	5870
qsz		Reserved for local use	5871
qta		Reserved for local use	5872
qtb		Reserved for local use	5873
qtc		Reserved for local use	5874
qtd		Reserved for local use	5875
qte		Reserved for local use	5876
qtf		Reserved for local use	5877
qtg		Reserved for local use	5878
qth		Reserved for local use	5879
qti		Reserved for local use	5880
qtj		Reserved for local use	5881
qtk		Reserved for local use	5882
qtl		Reserved for local use	5883
qtm		Reserved for local use	5884
qtn		Reserved for local use	5885
qto		Reserved for local use	5886
qtp		Reserved for local use	5887
qtq		Reserved for local use	5888
qtr		Reserved for local use	5889
qts		Reserved for local use	5890
qtt		Reserved for local use	5891
qtu		Reserved for local use	5892
qtv		Reserved for local use	5893
qtw		Reserved for local use	5894
qtx		Reserved for local use	5895
qty		Reserved for local use	5896
qtz		Reserved for local use	5897
qua		Quapaw	5898
qub		Huallaga Huánuco Quechua	5899
quc		K'iche'	5900
qud		Calderón Highland Quichua	5901
que	qu	Quechua	5902
quf		Lambayeque Quechua	5903
qug		Chimborazo Highland Quichua	5904
quh		South Bolivian Quechua	5905
qui		Quileute	5906
quk		Chachapoyas Quechua	5907
qul		North Bolivian Quechua	5908
qum		Sipacapense	5909
qun		Quinault	5910
qup		Southern Pastaza Quechua	5911
quq		Quinqui	5912
qur		Yanahuanca Pasco Quechua	5913
qus		Santiago del Estero Quichua	5914
quv		Sacapulteco	5915
quw		Tena Lowland Quichua	5916
qux		Yauyos Quechua	5917
quy		Ayacucho Quechua	5918
quz		Cusco Quechua	5919
qva		Ambo-Pasco Quechua	5920
qvc		Cajamarca Quechua	5921
qve		Eastern Apurímac Quechua	5922
qvh		Huamalíes-Dos de Mayo Huánuco Quechua	5923
qvi		Imbabura Highland Quichua	5924
qvj		Loja Highland Quichua	5925
qvl		Cajatambo North Lima Quechua	5926
qvm		Margos-Yarowilca-Lauricocha Quechua	5927
qvn		North Junín Quechua	5928
qvo		Napo Lowland Quechua	5929
qvp		Pacaraos Quechua	5930
qvs		San Martín Quechua	5931
qvw		Huaylla Wanca Quechua	5932
qvy		Queyu	5933
qvz		Northern Pastaza Quichua	5934
qwa		Corongo Ancash Quechua	5935
qwc		Classical Quechua	5936
qwh		Huaylas Ancash Quechua	5937
qwm		Kuman (Russia)	5938
qws		Sihuas Ancash Quechua	5939
qwt		Kwalhioqua-Tlatskanai	5940
qxa		Chiquián Ancash Quechua	5941
qxc		Chincha Quechua	5942
qxh		Panao Huánuco Quechua	5943
qxl		Salasaca Highland Quichua	5944
qxn		Northern Conchucos Ancash Quechua	5945
qxo		Southern Conchucos Ancash Quechua	5946
qxp		Puno Quechua	5947
qxq		Qashqa'i	5948
qxr		Cañar Highland Quichua	5949
qxs		Southern Qiang	5950
qxt		Santa Ana de Tusi Pasco Quechua	5951
qxu		Arequipa-La Unión Quechua	5952
qxw		Jauja Wanca Quechua	5953
qya		Quenya	5954
qyp		Quiripi	5955
raa		Dungmali	5956
rab		Camling	5957
rac		Rasawa	5958
rad		Rade	5959
raf		Western Meohang	5960
rag		Logooli	5961
rah		Rabha	5962
rai		Ramoaaina	5963
raj		Rajasthani	5964
rak		Tulu-Bohuai	5965
ral		Ralte	5966
ram		Canela	5967
ran		Riantana	5968
rao		Rao	5969
rap		Rapanui	5970
raq		Saam	5971
rar		Rarotongan	5972
ras		Tegali	5973
rat		Razajerdi	5974
rau		Raute	5975
rav		Sampang	5976
raw		Rawang	5977
rax		Rang	5978
ray		Rapa	5979
raz		Rahambuu	5980
rbb		Rumai Palaung	5981
rbk		Northern Bontok	5982
rbl		Miraya Bikol	5983
rcf		Réunion Creole French	5984
rdb		Rudbari	5985
rea		Rerau	5986
reb		Rembong	5987
ree		Rejang Kayan	5988
reg		Kara (Tanzania)	5989
rei		Reli	5990
rej		Rejang	5991
rel		Rendille	5992
rem		Remo	5993
ren		Rengao	5994
rer		Rer Bare	5995
res		Reshe	5996
ret		Retta	5997
rey		Reyesano	5998
rga		Roria	5999
rge		Romano-Greek	6000
rgk		Rangkas	6001
rgn		Romagnol	6002
rgr		Resígaro	6003
rgs		Southern Roglai	6004
rgu		Ringgou	6005
rhg		Rohingya	6006
rhp		Yahang	6007
ria		Riang (India)	6008
rie		Rien	6009
rif		Tarifit	6010
ril		Riang (Myanmar)	6011
rim		Nyaturu	6012
rin		Nungu	6013
rir		Ribun	6014
rit		Ritarungo	6015
riu		Riung	6016
rjg		Rajong	6017
rji		Raji	6018
rjs		Rajbanshi	6019
rka		Kraol	6020
rkb		Rikbaktsa	6021
rkh		Rakahanga-Manihiki	6022
rki		Rakhine	6023
rkm		Marka	6024
rkt		Rangpuri	6025
rma		Rama	6026
rmb		Rembarunga	6027
rmc		Carpathian Romani	6028
rmd		Traveller Danish	6029
rme		Angloromani	6030
rmf		Kalo Finnish Romani	6031
rmg		Traveller Norwegian	6032
rmh		Murkim	6033
rmi		Lomavren	6034
rmk		Romkun	6035
rml		Baltic Romani	6036
rmm		Roma	6037
rmn		Balkan Romani	6038
rmo		Sinte Romani	6039
rmp		Rempi	6040
rmq		Caló	6041
rms		Romanian Sign Language	6042
rmt		Domari	6043
rmu		Tavringer Romani	6044
rmv		Romanova	6045
rmw		Welsh Romani	6046
rmx		Romam	6047
rmy		Vlax Romani	6048
rmz		Marma	6049
rna		Runa	6050
rnd		Ruund	6051
rng		Ronga	6052
rnl		Ranglong	6053
rnn		Roon	6054
rnp		Rongpo	6055
rnw		Rungwa	6056
rob		Tae'	6057
roc		Cacgia Roglai	6058
rod		Rogo	6059
roe		Ronji	6060
rof		Rombo	6061
rog		Northern Roglai	6062
roh	rm	Romansh	6063
rol		Romblomanon	6064
rom		Romany	6065
ron	ro	Romanian	6066
roo		Rotokas	6067
rop		Kriol	6068
ror		Rongga	6069
rou		Runga	6070
row		Dela-Oenale	6071
rpn		Repanbitip	6072
rpt		Rapting	6073
rri		Ririo	6074
rro		Waima	6075
rsb		Romano-Serbian	6076
rsi		Rennellese Sign Language	6077
rsl		Russian Sign Language	6078
rth		Ratahan	6079
rtm		Rotuman	6080
rtw		Rathawi	6081
rub		Gungu	6082
ruc		Ruuli	6083
rue		Rusyn	6084
ruf		Luguru	6085
rug		Roviana	6086
ruh		Ruga	6087
rui		Rufiji	6088
ruk		Che	6089
run	rn	Rundi	6090
ruo		Istro Romanian	6091
rup		Macedo-Romanian	6092
ruq		Megleno Romanian	6093
rus	ru	Russian	6094
rut		Rutul	6095
ruu		Lanas Lobu	6096
ruy		Mala (Nigeria)	6097
ruz		Ruma	6098
rwa		Rawo	6099
rwk		Rwa	6100
rwm		Amba (Uganda)	6101
rwo		Rawa	6102
rwr		Marwari (India)	6103
ryn		Northern Amami-Oshima	6104
rys		Yaeyama	6105
ryu		Central Okinawan	6106
saa		Saba	6107
sab		Buglere	6108
sac		Meskwaki	6109
sad		Sandawe	6110
sae		Sabanê	6111
saf		Safaliba	6112
sag	sg	Sango	6113
sah		Yakut	6114
saj		Sahu	6115
sak		Sake	6116
sam		Samaritan Aramaic	6117
san	sa	Sanskrit	6118
sao		Sause	6119
sap		Sanapaná	6120
saq		Samburu	6121
sar		Saraveca	6122
sas		Sasak	6123
sat		Santali	6124
sau		Saleman	6125
sav		Saafi-Saafi	6126
saw		Sawi	6127
sax		Sa	6128
say		Saya	6129
saz		Saurashtra	6130
sba		Ngambay	6131
sbb		Simbo	6132
sbc		Kele (Papua New Guinea)	6133
sbd		Southern Samo	6134
sbe		Saliba	6135
sbf		Shabo	6136
sbg		Seget	6137
sbh		Sori-Harengan	6138
sbi		Seti	6139
sbj		Surbakhal	6140
sbk		Safwa	6141
sbl		Botolan Sambal	6142
sbm		Sagala	6143
sbn		Sindhi Bhil	6144
sbo		Sabüm	6145
sbp		Sangu (Tanzania)	6146
sbq		Sileibi	6147
sbr		Sembakung Murut	6148
sbs		Subiya	6149
sbt		Kimki	6150
sbu		Stod Bhoti	6151
sbv		Sabine	6152
sbw		Simba	6153
sbx		Seberuang	6154
sby		Soli	6155
sbz		Sara Kaba	6156
sca		Sansu	6157
scb		Chut	6158
sce		Dongxiang	6159
scf		San Miguel Creole French	6160
scg		Sanggau	6161
sch		Sakachep	6162
sci		Sri Lankan Creole Malay	6163
sck		Sadri	6164
scl		Shina	6165
scn		Sicilian	6166
sco		Scots	6167
scp		Helambu Sherpa	6168
scq		Sa'och	6169
scs		North Slavey	6170
scu		Shumcho	6171
scv		Sheni	6172
scw		Sha	6173
scx		Sicel	6174
sda		Toraja-Sa'dan	6175
sdb		Shabak	6176
sdc		Sassarese Sardinian	6177
sde		Surubu	6178
sdf		Sarli	6179
sdg		Savi	6180
sdh		Southern Kurdish	6181
sdj		Suundi	6182
sdk		Sos Kundi	6183
sdl		Saudi Arabian Sign Language	6184
sdm		Semandang	6185
sdn		Gallurese Sardinian	6186
sdo		Bukar-Sadung Bidayuh	6187
sdp		Sherdukpen	6188
sdr		Oraon Sadri	6189
sds		Sened	6190
sdt		Shuadit	6191
sdu		Sarudu	6192
sdx		Sibu Melanau	6193
sdz		Sallands	6194
sea		Semai	6195
seb		Shempire Senoufo	6196
sec		Sechelt	6197
sed		Sedang	6198
see		Seneca	6199
sef		Cebaara Senoufo	6200
seg		Segeju	6201
seh		Sena	6202
sei		Seri	6203
sej		Sene	6204
sek		Sekani	6205
sel		Selkup	6206
sen		Nanerigé Sénoufo	6207
seo		Suarmin	6208
sep		Sìcìté Sénoufo	6209
seq		Senara Sénoufo	6210
ser		Serrano	6211
ses		Koyraboro Senni Songhai	6212
set		Sentani	6213
seu		Serui-Laut	6214
sev		Nyarafolo Senoufo	6215
sew		Sewa Bay	6216
sey		Secoya	6217
sez		Senthang Chin	6218
sfb		Langue des signes de Belgique Francophone	6219
sfm		Small Flowery Miao	6220
sfs		South African Sign Language	6221
sfw		Sehwi	6222
sga		Old Irish (to 900)	6223
sgb		Mag-antsi Ayta	6224
sgc		Kipsigis	6225
sgd		Surigaonon	6226
sge		Segai	6227
sgg		Swiss-German Sign Language	6228
sgh		Shughni	6229
sgi		Suga	6230
sgk		Sangkong	6231
sgm		Singa	6232
sgo		Songa	6233
sgp		Singpho	6234
sgr		Sangisari	6235
sgs		Samogitian	6236
sgt		Brokpake	6237
sgu		Salas	6238
sgw		Sebat Bet Gurage	6239
sgx		Sierra Leone Sign Language	6240
sgy		Sanglechi	6241
sgz		Sursurunga	6242
sha		Shall-Zwall	6243
shb		Ninam	6244
shc		Sonde	6245
shd		Kundal Shahi	6246
she		Sheko	6247
shg		Shua	6248
shh		Shoshoni	6249
shi		Tachelhit	6250
shj		Shatt	6251
shk		Shilluk	6252
shl		Shendu	6253
shm		Shahrudi	6254
shn		Shan	6255
sho		Shanga	6256
shp		Shipibo-Conibo	6257
shq		Sala	6258
shr		Shi	6259
shs		Shuswap	6260
sht		Shasta	6261
shu		Chadian Arabic	6262
shv		Shehri	6263
shw		Shwai	6264
shx		She	6265
shy		Tachawit	6266
shz		Syenara Senoufo	6267
sia		Akkala Sami	6268
sib		Sebop	6269
sid		Sidamo	6270
sie		Simaa	6271
sif		Siamou	6272
sig		Paasaal	6273
sih		Zire	6274
sii		Shom Peng	6275
sij		Numbami	6276
sik		Sikiana	6277
sil		Tumulung Sisaala	6278
sim		Mende (Papua New Guinea)	6279
sin	si	Sinhala	6280
sip		Sikkimese	6281
siq		Sonia	6282
sir		Siri	6283
sis		Siuslaw	6284
siu		Sinagen	6285
siv		Sumariup	6286
siw		Siwai	6287
six		Sumau	6288
siy		Sivandi	6289
siz		Siwi	6290
sja		Epena	6291
sjb		Sajau Basap	6292
sjd		Kildin Sami	6293
sje		Pite Sami	6294
sjg		Assangori	6295
sjk		Kemi Sami	6296
sjl		Sajalong	6297
sjm		Mapun	6298
sjn		Sindarin	6299
sjo		Xibe	6300
sjp		Surjapuri	6301
sjr		Siar-Lak	6302
sjs		Senhaja De Srair	6303
sjt		Ter Sami	6304
sju		Ume Sami	6305
sjw		Shawnee	6306
ska		Skagit	6307
skb		Saek	6308
skc		Sauk	6309
skd		Southern Sierra Miwok	6310
ske		Seke (Vanuatu)	6311
skf		Sakirabiá	6312
skg		Sakalava Malagasy	6313
skh		Sikule	6314
ski		Sika	6315
skj		Seke (Nepal)	6316
skk		Sok	6317
skm		Sakam	6318
skn		Kolibugan Subanon	6319
sko		Seko Tengah	6320
skp		Sekapan	6321
skq		Sininkere	6322
skr		Seraiki	6323
sks		Maia	6324
skt		Sakata	6325
sku		Sakao	6326
skv		Skou	6327
skw		Skepi Creole Dutch	6328
skx		Seko Padang	6329
sky		Sikaiana	6330
skz		Sekar	6331
slc		Sáliba	6332
sld		Sissala	6333
sle		Sholaga	6334
slf		Swiss-Italian Sign Language	6335
slg		Selungai Murut	6336
slh		Southern Puget Sound Salish	6337
sli		Lower Silesian	6338
slj		Salumá	6339
slk	sk	Slovak	6340
sll		Salt-Yui	6341
slm		Pangutaran Sama	6342
sln		Salinan	6343
slp		Lamaholot	6344
slq		Salchuq	6345
slr		Salar	6346
sls		Singapore Sign Language	6347
slt		Sila	6348
slu		Selaru	6349
slv	sl	Slovenian	6350
slw		Sialum	6351
slx		Salampasu	6352
sly		Selayar	6353
slz		Ma'ya	6354
sma		Southern Sami	6355
smb		Simbari	6356
smc		Som	6357
smd		Sama	6358
sme	se	Northern Sami	6359
smf		Auwe	6360
smg		Simbali	6361
smh		Samei	6362
smj		Lule Sami	6363
smk		Bolinao	6364
sml		Central Sama	6365
smm		Musasa	6366
smn		Inari Sami	6367
smo	sm	Samoan	6368
smp		Samaritan	6369
smq		Samo	6370
smr		Simeulue	6371
sms		Skolt Sami	6372
smt		Simte	6373
smu		Somray	6374
smv		Samvedi	6375
smw		Sumbawa	6376
smx		Samba	6377
smy		Semnani	6378
smz		Simeku	6379
sna	sn	Shona	6380
snb		Sebuyau	6381
snc		Sinaugoro	6382
snd	sd	Sindhi	6383
sne		Bau Bidayuh	6384
snf		Noon	6385
sng		Sanga (Democratic Republic of Congo)	6386
snh		Shinabo	6387
sni		Sensi	6388
snj		Riverain Sango	6389
snk		Soninke	6390
snl		Sangil	6391
snm		Southern Ma'di	6392
snn		Siona	6393
sno		Snohomish	6394
snp		Siane	6395
snq		Sangu (Gabon)	6396
snr		Sihan	6397
sns		South West Bay	6398
snu		Senggi	6399
snv		Sa'ban	6400
snw		Selee	6401
snx		Sam	6402
sny		Saniyo-Hiyewe	6403
snz		Sinsauru	6404
soa		Thai Song	6405
sob		Sobei	6406
soc		So (Democratic Republic of Congo)	6407
sod		Songoora	6408
soe		Songomeno	6409
sog		Sogdian	6410
soh		Aka	6411
soi		Sonha	6412
soj		Soi	6413
sok		Sokoro	6414
sol		Solos	6415
som	so	Somali	6416
soo		Songo	6417
sop		Songe	6418
soq		Kanasi	6419
sor		Somrai	6420
sos		Seeku	6421
sot	st	Southern Sotho	6422
sou		Southern Thai	6423
sov		Sonsorol	6424
sow		Sowanda	6425
sox		So (Cameroon)	6426
soy		Miyobe	6427
soz		Temi	6428
spa	es	Spanish	6429
spb		Sepa (Indonesia)	6430
spc		Sapé	6431
spd		Saep	6432
spe		Sepa (Papua New Guinea)	6433
spg		Sian	6434
spi		Saponi	6435
spk		Sengo	6436
spl		Selepet	6437
spm		Sepen	6438
spo		Spokane	6439
spp		Supyire Senoufo	6440
spq		Loreto-Ucayali Spanish	6441
spr		Saparua	6442
sps		Saposa	6443
spt		Spiti Bhoti	6444
spu		Sapuan	6445
spx		South Picene	6446
spy		Sabaot	6447
sqa		Shama-Sambuga	6448
sqh		Shau	6449
sqi	sq	Albanian	6450
sqm		Suma	6451
sqn		Susquehannock	6452
sqo		Sorkhei	6453
sqq		Sou	6454
sqr		Siculo Arabic	6455
sqs		Sri Lankan Sign Language	6456
sqt		Soqotri	6457
squ		Squamish	6458
sra		Saruga	6459
srb		Sora	6460
src		Logudorese Sardinian	6461
srd	sc	Sardinian	6462
sre		Sara	6463
srf		Nafi	6464
srg		Sulod	6465
srh		Sarikoli	6466
sri		Siriano	6467
srk		Serudung Murut	6468
srl		Isirawa	6469
srm		Saramaccan	6470
srn		Sranan Tongo	6471
sro		Campidanese Sardinian	6472
srp	sr	Serbian	6473
srq		Sirionó	6474
srr		Serer	6475
srs		Sarsi	6476
srt		Sauri	6477
sru		Suruí	6478
srv		Southern Sorsoganon	6479
srw		Serua	6480
srx		Sirmauri	6481
sry		Sera	6482
srz		Shahmirzadi	6483
ssb		Southern Sama	6484
ssc		Suba-Simbiti	6485
ssd		Siroi	6486
sse		Balangingi	6487
ssf		Thao	6488
ssg		Seimat	6489
ssh		Shihhi Arabic	6490
ssi		Sansi	6491
ssj		Sausi	6492
ssk		Sunam	6493
ssl		Western Sisaala	6494
ssm		Semnam	6495
ssn		Waata	6496
sso		Sissano	6497
ssp		Spanish Sign Language	6498
ssq		So'a	6499
ssr		Swiss-French Sign Language	6500
sss		Sô	6501
sst		Sinasina	6502
ssu		Susuami	6503
ssv		Shark Bay	6504
ssw	ss	Swati	6505
ssx		Samberigi	6506
ssy		Saho	6507
ssz		Sengseng	6508
sta		Settla	6509
stb		Northern Subanen	6510
std		Sentinel	6511
ste		Liana-Seti	6512
stf		Seta	6513
stg		Trieng	6514
sth		Shelta	6515
sti		Bulo Stieng	6516
stj		Matya Samo	6517
stk		Arammba	6518
stl		Stellingwerfs	6519
stm		Setaman	6520
stn		Owa	6521
sto		Stoney	6522
stp		Southeastern Tepehuan	6523
stq		Saterfriesisch	6524
str		Straits Salish	6525
sts		Shumashti	6526
stt		Budeh Stieng	6527
stu		Samtao	6528
stv		Silt'e	6529
stw		Satawalese	6530
sua		Sulka	6531
sub		Suku	6532
suc		Western Subanon	6533
sue		Suena	6534
sug		Suganga	6535
sui		Suki	6536
suj		Shubi	6537
suk		Sukuma	6538
sun	su	Sundanese	6539
suq		Suri	6540
sur		Mwaghavul	6541
sus		Susu	6542
sut		Subtiaba	6543
suv		Sulung	6544
suw		Sumbwa	6545
sux		Sumerian	6546
suy		Suyá	6547
suz		Sunwar	6548
sva		Svan	6549
svb		Ulau-Suain	6550
svc		Vincentian Creole English	6551
sve		Serili	6552
svk		Slovakian Sign Language	6553
svr		Savara	6554
svs		Savosavo	6555
svx		Skalvian	6556
swa	sw	Swahili (macrolanguage)	6557
swb		Maore Comorian	6558
swc		Congo Swahili	6559
swe	sv	Swedish	6560
swf		Sere	6561
swg		Swabian	6562
swh		Swahili (individual language)	6563
swi		Sui	6564
swj		Sira	6565
swk		Malawi Sena	6566
swl		Swedish Sign Language	6567
swm		Samosa	6568
swn		Sawknah	6569
swo		Shanenawa	6570
swp		Suau	6571
swq		Sharwa	6572
swr		Saweru	6573
sws		Seluwasan	6574
swt		Sawila	6575
swu		Suwawa	6576
swv		Shekhawati	6577
sww		Sowa	6578
swx		Suruahá	6579
swy		Sarua	6580
sxb		Suba	6581
sxc		Sicanian	6582
sxe		Sighu	6583
sxg		Shixing	6584
sxk		Southern Kalapuya	6585
sxl		Selian	6586
sxm		Samre	6587
sxn		Sangir	6588
sxo		Sorothaptic	6589
sxr		Saaroa	6590
sxs		Sasaru	6591
sxu		Upper Saxon	6592
sxw		Saxwe Gbe	6593
sya		Siang	6594
syb		Central Subanen	6595
syc		Classical Syriac	6596
syi		Seki	6597
syk		Sukur	6598
syl		Sylheti	6599
sym		Maya Samo	6600
syn		Senaya	6601
syo		Suoy	6602
syr		Syriac	6603
sys		Sinyar	6604
syw		Kagate	6605
syy		Al-Sayyid Bedouin Sign Language	6606
sza		Semelai	6607
szb		Ngalum	6608
szc		Semaq Beri	6609
szd		Seru	6610
sze		Seze	6611
szg		Sengele	6612
szl		Silesian	6613
szn		Sula	6614
szp		Suabo	6615
szv		Isu (Fako Division)	6616
szw		Sawai	6617
taa		Lower Tanana	6618
tab		Tabassaran	6619
tac		Lowland Tarahumara	6620
tad		Tause	6621
tae		Tariana	6622
taf		Tapirapé	6623
tag		Tagoi	6624
tah	ty	Tahitian	6625
taj		Eastern Tamang	6626
tak		Tala	6627
tal		Tal	6628
tam	ta	Tamil	6629
tan		Tangale	6630
tao		Yami	6631
tap		Taabwa	6632
taq		Tamasheq	6633
tar		Central Tarahumara	6634
tas		Tay Boi	6635
tat	tt	Tatar	6636
tau		Upper Tanana	6637
tav		Tatuyo	6638
taw		Tai	6639
tax		Tamki	6640
tay		Atayal	6641
taz		Tocho	6642
tba		Aikanã	6643
tbb		Tapeba	6644
tbc		Takia	6645
tbd		Kaki Ae	6646
tbe		Tanimbili	6647
tbf		Mandara	6648
tbg		North Tairora	6649
tbh		Thurawal	6650
tbi		Gaam	6651
tbj		Tiang	6652
tbk		Calamian Tagbanwa	6653
tbl		Tboli	6654
tbm		Tagbu	6655
tbn		Barro Negro Tunebo	6656
tbo		Tawala	6657
tbp		Taworta	6658
tbr		Tumtum	6659
tbs		Tanguat	6660
tbt		Tembo (Kitembo)	6661
tbu		Tubar	6662
tbv		Tobo	6663
tbw		Tagbanwa	6664
tbx		Kapin	6665
tby		Tabaru	6666
tbz		Ditammari	6667
tca		Ticuna	6668
tcb		Tanacross	6669
tcc		Datooga	6670
tcd		Tafi	6671
tce		Southern Tutchone	6672
tcf		Malinaltepec Me'phaa	6673
tcg		Tamagario	6674
tch		Turks And Caicos Creole English	6675
tci		Wára	6676
tck		Tchitchege	6677
tcl		Taman (Myanmar)	6678
tcm		Tanahmerah	6679
tcn		Tichurong	6680
tco		Taungyo	6681
tcp		Tawr Chin	6682
tcq		Kaiy	6683
tcs		Torres Strait Creole	6684
tct		T'en	6685
tcu		Southeastern Tarahumara	6686
tcw		Tecpatlán Totonac	6687
tcx		Toda	6688
tcy		Tulu	6689
tcz		Thado Chin	6690
tda		Tagdal	6691
tdb		Panchpargania	6692
tdc		Emberá-Tadó	6693
tdd		Tai Nüa	6694
tde		Tiranige Diga Dogon	6695
tdf		Talieng	6696
tdg		Western Tamang	6697
tdh		Thulung	6698
tdi		Tomadino	6699
tdj		Tajio	6700
tdk		Tambas	6701
tdl		Sur	6702
tdn		Tondano	6703
tdo		Teme	6704
tdq		Tita	6705
tdr		Todrah	6706
tds		Doutai	6707
tdt		Tetun Dili	6708
tdu		Tempasuk Dusun	6709
tdv		Toro	6710
tdx		Tandroy-Mahafaly Malagasy	6711
tdy		Tadyawan	6712
tea		Temiar	6713
teb		Tetete	6714
tec		Terik	6715
ted		Tepo Krumen	6716
tee		Huehuetla Tepehua	6717
tef		Teressa	6718
teg		Teke-Tege	6719
teh		Tehuelche	6720
tei		Torricelli	6721
tek		Ibali Teke	6722
tel	te	Telugu	6723
tem		Timne	6724
ten		Tama (Colombia)	6725
teo		Teso	6726
tep		Tepecano	6727
teq		Temein	6728
ter		Tereno	6729
tes		Tengger	6730
tet		Tetum	6731
teu		Soo	6732
tev		Teor	6733
tew		Tewa (USA)	6734
tex		Tennet	6735
tey		Tulishi	6736
tfi		Tofin Gbe	6737
tfn		Tanaina	6738
tfo		Tefaro	6739
tfr		Teribe	6740
tft		Ternate	6741
tga		Sagalla	6742
tgb		Tobilung	6743
tgc		Tigak	6744
tgd		Ciwogai	6745
tge		Eastern Gorkha Tamang	6746
tgf		Chalikha	6747
tgg		Tangga	6748
tgh		Tobagonian Creole English	6749
tgi		Lawunuia	6750
tgk	tg	Tajik	6751
tgl	tl	Tagalog	6752
tgn		Tandaganon	6753
tgo		Sudest	6754
tgp		Tangoa	6755
tgq		Tring	6756
tgr		Tareng	6757
tgs		Nume	6758
tgt		Central Tagbanwa	6759
tgu		Tanggu	6760
tgv		Tingui-Boto	6761
tgw		Tagwana Senoufo	6762
tgx		Tagish	6763
tgy		Togoyo	6764
tha	th	Thai	6765
thc		Tai Hang Tong	6766
thd		Thayore	6767
the		Chitwania Tharu	6768
thf		Thangmi	6769
thh		Northern Tarahumara	6770
thi		Tai Long	6771
thk		Tharaka	6772
thl		Dangaura Tharu	6773
thm		Aheu	6774
thn		Thachanadan	6775
thp		Thompson	6776
thq		Kochila Tharu	6777
thr		Rana Tharu	6778
ths		Thakali	6779
tht		Tahltan	6780
thu		Thuri	6781
thv		Tahaggart Tamahaq	6782
thw		Thudam	6783
thx		The	6784
thy		Tha	6785
thz		Tayart Tamajeq	6786
tia		Tidikelt Tamazight	6787
tic		Tira	6788
tid		Tidong	6789
tie		Tingal	6790
tif		Tifal	6791
tig		Tigre	6792
tih		Timugon Murut	6793
tii		Tiene	6794
tij		Tilung	6795
tik		Tikar	6796
til		Tillamook	6797
tim		Timbe	6798
tin		Tindi	6799
tio		Teop	6800
tip		Trimuris	6801
tiq		Tiéfo	6802
tir	ti	Tigrinya	6803
tis		Masadiit Itneg	6804
tit		Tinigua	6805
tiu		Adasen	6806
tiv		Tiv	6807
tiw		Tiwi	6808
tix		Southern Tiwa	6809
tiy		Tiruray	6810
tiz		Tai Hongjin	6811
tja		Tajuasohn	6812
tjg		Tunjung	6813
tji		Northern Tujia	6814
tjm		Timucua	6815
tjn		Tonjon	6816
tjo		Temacine Tamazight	6817
tjs		Southern Tujia	6818
tju		Tjurruru	6819
tka		Truká	6820
tkb		Buksa	6821
tkd		Tukudede	6822
tke		Takwane	6823
tkf		Tukumanféd	6824
tkk		Takpa	6825
tkl		Tokelau	6826
tkm		Takelma	6827
tkn		Toku-No-Shima	6828
tkp		Tikopia	6829
tkq		Tee	6830
tkr		Tsakhur	6831
tks		Takestani	6832
tkt		Kathoriya Tharu	6833
tku		Upper Necaxa Totonac	6834
tkw		Teanu	6835
tkx		Tangko	6836
tkz		Takua	6837
tla		Southwestern Tepehuan	6838
tlb		Tobelo	6839
tlc		Yecuatla Totonac	6840
tld		Talaud	6841
tlf		Telefol	6842
tlg		Tofanma	6843
tlh		Klingon	6844
tli		Tlingit	6845
tlj		Talinga-Bwisi	6846
tlk		Taloki	6847
tll		Tetela	6848
tlm		Tolomako	6849
tln		Talondo'	6850
tlo		Talodi	6851
tlp		Filomena Mata-Coahuitlán Totonac	6852
tlq		Tai Loi	6853
tlr		Talise	6854
tls		Tambotalo	6855
tlt		Teluti	6856
tlu		Tulehu	6857
tlv		Taliabu	6858
tlw		South Wemale	6859
tlx		Khehek	6860
tly		Talysh	6861
tma		Tama (Chad)	6862
tmb		Katbol	6863
tmc		Tumak	6864
tmd		Haruai	6865
tme		Tremembé	6866
tmf		Toba-Maskoy	6867
tmg		Ternateño	6868
tmh		Tamashek	6869
tmi		Tutuba	6870
tmj		Samarokena	6871
tmk		Northwestern Tamang	6872
tml		Tamnim Citak	6873
tmm		Tai Thanh	6874
tmn		Taman (Indonesia)	6875
tmo		Temoq	6876
tmp		Tai Mène	6877
tmq		Tumleo	6878
tmr		Jewish Babylonian Aramaic (ca. 200-1200 CE)	6879
tms		Tima	6880
tmt		Tasmate	6881
tmu		Iau	6882
tmv		Tembo (Motembo)	6883
tmw		Temuan	6884
tmy		Tami	6885
tmz		Tamanaku	6886
tna		Tacana	6887
tnb		Western Tunebo	6888
tnc		Tanimuca-Retuarã	6889
tnd		Angosturas Tunebo	6890
tne		Tinoc Kallahan	6891
tng		Tobanga	6892
tnh		Maiani	6893
tni		Tandia	6894
tnk		Kwamera	6895
tnl		Lenakel	6896
tnm		Tabla	6897
tnn		North Tanna	6898
tno		Toromono	6899
tnp		Whitesands	6900
tnq		Taino	6901
tnr		Bedik	6902
tns		Tenis	6903
tnt		Tontemboan	6904
tnu		Tay Khang	6905
tnv		Tangchangya	6906
tnw		Tonsawang	6907
tnx		Tanema	6908
tny		Tongwe	6909
tnz		Tonga (Thailand)	6910
tob		Toba	6911
toc		Coyutla Totonac	6912
tod		Toma	6913
toe		Tomedes	6914
tof		Gizrra	6915
tog		Tonga (Nyasa)	6916
toh		Gitonga	6917
toi		Tonga (Zambia)	6918
toj		Tojolabal	6919
tol		Tolowa	6920
tom		Tombulu	6921
ton	to	Tonga (Tonga Islands)	6922
too		Xicotepec De Juárez Totonac	6923
top		Papantla Totonac	6924
toq		Toposa	6925
tor		Togbo-Vara Banda	6926
tos		Highland Totonac	6927
tou		Tho	6928
tov		Upper Taromi	6929
tow		Jemez	6930
tox		Tobian	6931
toy		Topoiyo	6932
toz		To	6933
tpa		Taupota	6934
tpc		Azoyú Me'phaa	6935
tpe		Tippera	6936
tpf		Tarpia	6937
tpg		Kula	6938
tpi		Tok Pisin	6939
tpj		Tapieté	6940
tpk		Tupinikin	6941
tpl		Tlacoapa Me'phaa	6942
tpm		Tampulma	6943
tpn		Tupinambá	6944
tpo		Tai Pao	6945
tpp		Pisaflores Tepehua	6946
tpq		Tukpa	6947
tpr		Tuparí	6948
tpt		Tlachichilco Tepehua	6949
tpu		Tampuan	6950
tpv		Tanapag	6951
tpw		Tupí	6952
tpx		Acatepec Me'phaa	6953
tpy		Trumai	6954
tpz		Tinputz	6955
tqb		Tembé	6956
tql		Lehali	6957
tqm		Turumsa	6958
tqn		Tenino	6959
tqo		Toaripi	6960
tqp		Tomoip	6961
tqq		Tunni	6962
tqr		Torona	6963
tqt		Western Totonac	6964
tqu		Touo	6965
tqw		Tonkawa	6966
tra		Tirahi	6967
trb		Terebu	6968
trc		Copala Triqui	6969
trd		Turi	6970
tre		East Tarangan	6971
trf		Trinidadian Creole English	6972
trg		Lishán Didán	6973
trh		Turaka	6974
tri		Trió	6975
trj		Toram	6976
trl		Traveller Scottish	6977
trm		Tregami	6978
trn		Trinitario	6979
tro		Tarao Naga	6980
trp		Kok Borok	6981
trq		San Martín Itunyoso Triqui	6982
trr		Taushiro	6983
trs		Chicahuaxtla Triqui	6984
trt		Tunggare	6985
tru		Turoyo	6986
trv		Taroko	6987
trw		Torwali	6988
trx		Tringgus-Sembaan Bidayuh	6989
try		Turung	6990
trz		Torá	6991
tsa		Tsaangi	6992
tsb		Tsamai	6993
tsc		Tswa	6994
tsd		Tsakonian	6995
tse		Tunisian Sign Language	6996
tsf		Southwestern Tamang	6997
tsg		Tausug	6998
tsh		Tsuvan	6999
tsi		Tsimshian	7000
tsj		Tshangla	7001
tsk		Tseku	7002
tsl		Ts'ün-Lao	7003
tsm		Turkish Sign Language	7004
tsn	tn	Tswana	7005
tso	ts	Tsonga	7006
tsp		Northern Toussian	7007
tsq		Thai Sign Language	7008
tsr		Akei	7009
tss		Taiwan Sign Language	7010
tsu		Tsou	7011
tsv		Tsogo	7012
tsw		Tsishingini	7013
tsx		Mubami	7014
tsy		Tebul Sign Language	7015
tsz		Purepecha	7016
tta		Tutelo	7017
ttb		Gaa	7018
ttc		Tektiteko	7019
ttd		Tauade	7020
tte		Bwanabwana	7021
ttf		Tuotomb	7022
ttg		Tutong	7023
tth		Upper Ta'oih	7024
tti		Tobati	7025
ttj		Tooro	7026
ttk		Totoro	7027
ttl		Totela	7028
ttm		Northern Tutchone	7029
ttn		Towei	7030
tto		Lower Ta'oih	7031
ttp		Tombelala	7032
ttq		Tawallammat Tamajaq	7033
ttr		Tera	7034
tts		Northeastern Thai	7035
ttt		Muslim Tat	7036
ttu		Torau	7037
ttv		Titan	7038
ttw		Long Wat	7039
tty		Sikaritai	7040
ttz		Tsum	7041
tua		Wiarumus	7042
tub		Tübatulabal	7043
tuc		Mutu	7044
tud		Tuxá	7045
tue		Tuyuca	7046
tuf		Central Tunebo	7047
tug		Tunia	7048
tuh		Taulil	7049
tui		Tupuri	7050
tuj		Tugutil	7051
tuk	tk	Turkmen	7052
tul		Tula	7053
tum		Tumbuka	7054
tun		Tunica	7055
tuo		Tucano	7056
tuq		Tedaga	7057
tur	tr	Turkish	7058
tus		Tuscarora	7059
tuu		Tututni	7060
tuv		Turkana	7061
tux		Tuxináwa	7062
tuy		Tugen	7063
tuz		Turka	7064
tva		Vaghua	7065
tvd		Tsuvadi	7066
tve		Te'un	7067
tvk		Southeast Ambrym	7068
tvl		Tuvalu	7069
tvm		Tela-Masbuar	7070
tvn		Tavoyan	7071
tvo		Tidore	7072
tvs		Taveta	7073
tvt		Tutsa Naga	7074
tvw		Sedoa	7075
tvy		Timor Pidgin	7076
twa		Twana	7077
twb		Western Tawbuid	7078
twc		Teshenawa	7079
twd		Twents	7080
twe		Tewa (Indonesia)	7081
twf		Northern Tiwa	7082
twg		Tereweng	7083
twh		Tai Dón	7084
twi	tw	Twi	7085
twl		Tawara	7086
twm		Tawang Monpa	7087
twn		Twendi	7088
two		Tswapong	7089
twp		Ere	7090
twq		Tasawaq	7091
twr		Southwestern Tarahumara	7092
twt		Turiwára	7093
twu		Termanu	7094
tww		Tuwari	7095
twx		Tewe	7096
twy		Tawoyan	7097
txa		Tombonuo	7098
txb		Tokharian B	7099
txc		Tsetsaut	7100
txe		Totoli	7101
txg		Tangut	7102
txh		Thracian	7103
txi		Ikpeng	7104
txm		Tomini	7105
txn		West Tarangan	7106
txo		Toto	7107
txq		Tii	7108
txr		Tartessian	7109
txs		Tonsea	7110
txt		Citak	7111
txu		Kayapó	7112
txx		Tatana	7113
txy		Tanosy Malagasy	7114
tya		Tauya	7115
tye		Kyenga	7116
tyh		O'du	7117
tyi		Teke-Tsaayi	7118
tyj		Tai Do	7119
tyl		Thu Lao	7120
tyn		Kombai	7121
typ		Thaypan	7122
tyr		Tai Daeng	7123
tys		Tày Sa Pa	7124
tyt		Tày Tac	7125
tyu		Kua	7126
tyv		Tuvinian	7127
tyx		Teke-Tyee	7128
tyz		Tày	7129
tza		Tanzanian Sign Language	7130
tzh		Tzeltal	7131
tzj		Tz'utujil	7132
tzm		Central Atlas Tamazight	7133
tzn		Tugun	7134
tzo		Tzotzil	7135
tzx		Tabriak	7136
uam		Uamué	7137
uan		Kuan	7138
uar		Tairuma	7139
uba		Ubang	7140
ubi		Ubi	7141
ubl		Buhi'non Bikol	7142
ubr		Ubir	7143
ubu		Umbu-Ungu	7144
uby		Ubykh	7145
uda		Uda	7146
ude		Udihe	7147
udg		Muduga	7148
udi		Udi	7149
udj		Ujir	7150
udl		Wuzlam	7151
udm		Udmurt	7152
udu		Uduk	7153
ues		Kioko	7154
ufi		Ufim	7155
uga		Ugaritic	7156
ugb		Kuku-Ugbanh	7157
uge		Ughele	7158
ugn		Ugandan Sign Language	7159
ugo		Ugong	7160
ugy		Uruguayan Sign Language	7161
uha		Uhami	7162
uhn		Damal	7163
uig	ug	Uighur	7164
uis		Uisai	7165
uiv		Iyive	7166
uji		Tanjijili	7167
uka		Kaburi	7168
ukg		Ukuriguma	7169
ukh		Ukhwejo	7170
ukl		Ukrainian Sign Language	7171
ukp		Ukpe-Bayobiri	7172
ukq		Ukwa	7173
ukr	uk	Ukrainian	7174
uks		Urubú-Kaapor Sign Language	7175
uku		Ukue	7176
ukw		Ukwuani-Aboh-Ndoni	7177
ula		Fungwa	7178
ulb		Ulukwumi	7179
ulc		Ulch	7180
ulf		Usku	7181
uli		Ulithian	7182
ulk		Meriam	7183
ull		Ullatan	7184
ulm		Ulumanda'	7185
uln		Unserdeutsch	7186
ulu		Uma' Lung	7187
ulw		Ulwa	7188
uma		Umatilla	7189
umb		Umbundu	7190
umc		Marrucinian	7191
umd		Umbindhamu	7192
umg		Umbuygamu	7193
umi		Ukit	7194
umm		Umon	7195
umn		Makyan Naga	7196
umo		Umotína	7197
ump		Umpila	7198
umr		Umbugarla	7199
ums		Pendau	7200
umu		Munsee	7201
una		North Watut	7202
und		Undetermined	7203
une		Uneme	7204
ung		Ngarinyin	7205
unk		Enawené-Nawé	7206
unm		Unami	7207
unp		Worora	7208
unr		Mundari	7209
unx		Munda	7210
unz		Unde Kaili	7211
uok		Uokha	7212
upi		Umeda	7213
upv		Uripiv-Wala-Rano-Atchin	7214
ura		Urarina	7215
urb		Urubú-Kaapor	7216
urc		Urningangg	7217
urd	ur	Urdu	7218
ure		Uru	7219
urf		Uradhi	7220
urg		Urigina	7221
urh		Urhobo	7222
uri		Urim	7223
urk		Urak Lawoi'	7224
url		Urali	7225
urm		Urapmin	7226
urn		Uruangnirin	7227
uro		Ura (Papua New Guinea)	7228
urp		Uru-Pa-In	7229
urr		Lehalurup	7230
urt		Urat	7231
uru		Urumi	7232
urv		Uruava	7233
urw		Sop	7234
urx		Urimo	7235
ury		Orya	7236
urz		Uru-Eu-Wau-Wau	7237
usa		Usarufa	7238
ush		Ushojo	7239
usi		Usui	7240
usk		Usaghade	7241
usp		Uspanteco	7242
usu		Uya	7243
uta		Otank	7244
ute		Ute-Southern Paiute	7245
utp		Amba (Solomon Islands)	7246
utr		Etulo	7247
utu		Utu	7248
uum		Urum	7249
uun		Kulon-Pazeh	7250
uur		Ura (Vanuatu)	7251
uuu		U	7252
uve		West Uvean	7253
uvh		Uri	7254
uvl		Lote	7255
uwa		Kuku-Uwanh	7256
uya		Doko-Uyanga	7257
uzb	uz	Uzbek	7258
uzn		Northern Uzbek	7259
uzs		Southern Uzbek	7260
vaa		Vaagri Booli	7261
vae		Vale	7262
vaf		Vafsi	7263
vag		Vagla	7264
vah		Varhadi-Nagpuri	7265
vai		Vai	7266
vaj		Vasekela Bushman	7267
val		Vehes	7268
vam		Vanimo	7269
van		Valman	7270
vao		Vao	7271
vap		Vaiphei	7272
var		Huarijio	7273
vas		Vasavi	7274
vau		Vanuma	7275
vav		Varli	7276
vay		Wayu	7277
vbb		Southeast Babar	7278
vbk		Southwestern Bontok	7279
vec		Venetian	7280
ved		Veddah	7281
vel		Veluws	7282
vem		Vemgo-Mabas	7283
ven	ve	Venda	7284
veo		Ventureño	7285
vep		Veps	7286
ver		Mom Jango	7287
vgr		Vaghri	7288
vgt		Vlaamse Gebarentaal	7289
vic		Virgin Islands Creole English	7290
vid		Vidunda	7291
vie	vi	Vietnamese	7292
vif		Vili	7293
vig		Viemo	7294
vil		Vilela	7295
vin		Vinza	7296
vis		Vishavan	7297
vit		Viti	7298
viv		Iduna	7299
vka		Kariyarra	7300
vki		Ija-Zuba	7301
vkj		Kujarge	7302
vkk		Kaur	7303
vkl		Kulisusu	7304
vkm		Kamakan	7305
vko		Kodeoha	7306
vkp		Korlai Creole Portuguese	7307
vkt		Tenggarong Kutai Malay	7308
vku		Kurrama	7309
vlp		Valpei	7310
vls		Vlaams	7311
vma		Martuyhunira	7312
vmb		Mbabaram	7313
vmc		Juxtlahuaca Mixtec	7314
vmd		Mudu Koraga	7315
vme		East Masela	7316
vmf		Mainfränkisch	7317
vmg		Minigir	7318
vmh		Maraghei	7319
vmi		Miwa	7320
vmj		Ixtayutla Mixtec	7321
vmk		Makhuwa-Shirima	7322
vml		Malgana	7323
vmm		Mitlatongo Mixtec	7324
vmp		Soyaltepec Mazatec	7325
vmq		Soyaltepec Mixtec	7326
vmr		Marenje	7327
vms		Moksela	7328
vmu		Muluridyi	7329
vmv		Valley Maidu	7330
vmw		Makhuwa	7331
vmx		Tamazola Mixtec	7332
vmy		Ayautla Mazatec	7333
vmz		Mazatlán Mazatec	7334
vnk		Vano	7335
vnm		Vinmavis	7336
vnp		Vunapu	7337
vol	vo	Volapük	7338
vor		Voro	7339
vot		Votic	7340
vra		Vera'a	7341
vro		Võro	7342
vrs		Varisi	7343
vrt		Burmbar	7344
vsi		Moldova Sign Language	7345
vsl		Venezuelan Sign Language	7346
vsv		Valencian Sign Language	7347
vto		Vitou	7348
vum		Vumbu	7349
vun		Vunjo	7350
vut		Vute	7351
vwa		Awa (China)	7352
waa		Walla Walla	7353
wab		Wab	7354
wac		Wasco-Wishram	7355
wad		Wandamen	7356
wae		Walser	7357
waf		Wakoná	7358
wag		Wa'ema	7359
wah		Watubela	7360
wai		Wares	7361
waj		Waffa	7362
wal		Wolaytta	7363
wam		Wampanoag	7364
wan		Wan	7365
wao		Wappo	7366
wap		Wapishana	7367
waq		Wageman	7368
war		Waray (Philippines)	7369
was		Washo	7370
wat		Kaninuwa	7371
wau		Waurá	7372
wav		Waka	7373
waw		Waiwai	7374
wax		Watam	7375
way		Wayana	7376
waz		Wampur	7377
wba		Warao	7378
wbb		Wabo	7379
wbe		Waritai	7380
wbf		Wara	7381
wbh		Wanda	7382
wbi		Vwanji	7383
wbj		Alagwa	7384
wbk		Waigali	7385
wbl		Wakhi	7386
wbm		Wa	7387
wbp		Warlpiri	7388
wbq		Waddar	7389
wbr		Wagdi	7390
wbt		Wanman	7391
wbv		Wajarri	7392
wbw		Woi	7393
wca		Yanomámi	7394
wci		Waci Gbe	7395
wdd		Wandji	7396
wdg		Wadaginam	7397
wdj		Wadjiginy	7398
wdu		Wadjigu	7399
wea		Wewaw	7400
wec		Wè Western	7401
wed		Wedau	7402
weh		Weh	7403
wei		Were	7404
wem		Weme Gbe	7405
weo		North Wemale	7406
wep		Westphalien	7407
wer		Weri	7408
wes		Cameroon Pidgin	7409
wet		Perai	7410
weu		Welaung	7411
wew		Wejewa	7412
wfg		Yafi	7413
wga		Wagaya	7414
wgb		Wagawaga	7415
wgg		Wangganguru	7416
wgi		Wahgi	7417
wgo		Waigeo	7418
wgy		Warrgamay	7419
wha		Manusela	7420
whg		North Wahgi	7421
whk		Wahau Kenyah	7422
whu		Wahau Kayan	7423
wib		Southern Toussian	7424
wic		Wichita	7425
wie		Wik-Epa	7426
wif		Wik-Keyangan	7427
wig		Wik-Ngathana	7428
wih		Wik-Me'anha	7429
wii		Minidien	7430
wij		Wik-Iiyanh	7431
wik		Wikalkan	7432
wil		Wilawila	7433
wim		Wik-Mungkan	7434
win		Ho-Chunk	7435
wir		Wiraféd	7436
wit		Wintu	7437
wiu		Wiru	7438
wiv		Muduapa	7439
wiw		Wirangu	7440
wiy		Wiyot	7441
wja		Waja	7442
wji		Warji	7443
wka		Kw'adza	7444
wkb		Kumbaran	7445
wkd		Wakde	7446
wkl		Kalanadi	7447
wku		Kunduvadi	7448
wkw		Wakawaka	7449
wla		Walio	7450
wlc		Mwali Comorian	7451
wle		Wolane	7452
wlg		Kunbarlang	7453
wli		Waioli	7454
wlk		Wailaki	7455
wll		Wali (Sudan)	7456
wlm		Middle Welsh	7457
wln	wa	Walloon	7458
wlo		Wolio	7459
wlr		Wailapa	7460
wls		Wallisian	7461
wlu		Wuliwuli	7462
wlv		Wichí Lhamtés Vejoz	7463
wlw		Walak	7464
wlx		Wali (Ghana)	7465
wly		Waling	7466
wma		Mawa (Nigeria)	7467
wmb		Wambaya	7468
wmc		Wamas	7469
wmd		Mamaindé	7470
wme		Wambule	7471
wmh		Waima'a	7472
wmi		Wamin	7473
wmm		Maiwa (Indonesia)	7474
wmn		Waamwang	7475
wmo		Wom (Papua New Guinea)	7476
wms		Wambon	7477
wmt		Walmajarri	7478
wmw		Mwani	7479
wmx		Womo	7480
wnb		Wanambre	7481
wnc		Wantoat	7482
wnd		Wandarang	7483
wne		Waneci	7484
wng		Wanggom	7485
wni		Ndzwani Comorian	7486
wnk		Wanukaka	7487
wnm		Wanggamala	7488
wno		Wano	7489
wnp		Wanap	7490
wnu		Usan	7491
woa		Tyaraity	7492
wob		Wè Northern	7493
woc		Wogeo	7494
wod		Wolani	7495
woe		Woleaian	7496
wof		Gambian Wolof	7497
wog		Wogamusin	7498
woi		Kamang	7499
wok		Longto	7500
wol	wo	Wolof	7501
wom		Wom (Nigeria)	7502
won		Wongo	7503
woo		Manombai	7504
wor		Woria	7505
wos		Hanga Hundi	7506
wow		Wawonii	7507
woy		Weyto	7508
wpc		Maco	7509
wra		Warapu	7510
wrb		Warluwara	7511
wrd		Warduji	7512
wrg		Warungu	7513
wrh		Wiradhuri	7514
wri		Wariyangga	7515
wrl		Warlmanpa	7516
wrm		Warumungu	7517
wrn		Warnang	7518
wrp		Waropen	7519
wrr		Wardaman	7520
wrs		Waris	7521
wru		Waru	7522
wrv		Waruna	7523
wrw		Gugu Warra	7524
wrx		Wae Rana	7525
wry		Merwari	7526
wrz		Waray (Australia)	7527
wsa		Warembori	7528
wsi		Wusi	7529
wsk		Waskia	7530
wsr		Owenia	7531
wss		Wasa	7532
wsu		Wasu	7533
wsv		Wotapuri-Katarqalai	7534
wtf		Dumpu	7535
wti		Berta	7536
wtk		Watakataui	7537
wtm		Mewati	7538
wtw		Wotu	7539
wua		Wikngenchera	7540
wub		Wunambal	7541
wud		Wudu	7542
wuh		Wutunhua	7543
wul		Silimo	7544
wum		Wumbvu	7545
wun		Bungu	7546
wur		Wurrugu	7547
wut		Wutung	7548
wuu		Wu Chinese	7549
wuv		Wuvulu-Aua	7550
wux		Wulna	7551
wuy		Wauyai	7552
wwa		Waama	7553
wwo		Wetamut	7554
wwr		Warrwa	7555
www		Wawa	7556
wxa		Waxianghua	7557
wya		Wyandot	7558
wyb		Wangaaybuwan-Ngiyambaa	7559
wym		Wymysorys	7560
wyr		Wayoró	7561
wyy		Western Fijian	7562
xaa		Andalusian Arabic	7563
xab		Sambe	7564
xac		Kachari	7565
xad		Adai	7566
xae		Aequian	7567
xag		Aghwan	7568
xai		Kaimbé	7569
xal		Kalmyk	7570
xam		/Xam	7571
xan		Xamtanga	7572
xao		Khao	7573
xap		Apalachee	7574
xaq		Aquitanian	7575
xar		Karami	7576
xas		Kamas	7577
xat		Katawixi	7578
xau		Kauwera	7579
xav		Xavánte	7580
xaw		Kawaiisu	7581
xay		Kayan Mahakam	7582
xba		Kamba (Brazil)	7583
xbb		Lower Burdekin	7584
xbc		Bactrian	7585
xbi		Kombio	7586
xbm		Middle Breton	7587
xbn		Kenaboi	7588
xbo		Bolgarian	7589
xbr		Kambera	7590
xbw		Kambiwá	7591
xbx		Kabixí	7592
xcb		Cumbric	7593
xcc		Camunic	7594
xce		Celtiberian	7595
xcg		Cisalpine Gaulish	7596
xch		Chemakum	7597
xcl		Classical Armenian	7598
xcm		Comecrudo	7599
xcn		Cotoname	7600
xco		Chorasmian	7601
xcr		Carian	7602
xct		Classical Tibetan	7603
xcu		Curonian	7604
xcv		Chuvantsy	7605
xcw		Coahuilteco	7606
xcy		Cayuse	7607
xdc		Dacian	7608
xdm		Edomite	7609
xdy		Malayic Dayak	7610
xeb		Eblan	7611
xed		Hdi	7612
xeg		//Xegwi	7613
xel		Kelo	7614
xem		Kembayan	7615
xep		Epi-Olmec	7616
xer		Xerénte	7617
xes		Kesawai	7618
xet		Xetá	7619
xeu		Keoru-Ahia	7620
xfa		Faliscan	7621
xga		Galatian	7622
xgf		Gabrielino-Fernandeño	7623
xgl		Galindan	7624
xgr		Garza	7625
xha		Harami	7626
xhc		Hunnic	7627
xhd		Hadrami	7628
xhe		Khetrani	7629
xho	xh	Xhosa	7630
xhr		Hernican	7631
xht		Hattic	7632
xhu		Hurrian	7633
xhv		Khua	7634
xia		Xiandao	7635
xib		Iberian	7636
xii		Xiri	7637
xil		Illyrian	7638
xin		Xinca	7639
xip		Xipináwa	7640
xir		Xiriâna	7641
xiv		Indus Valley Language	7642
xiy		Xipaya	7643
xka		Kalkoti	7644
xkb		Northern Nago	7645
xkc		Kho'ini	7646
xkd		Mendalam Kayan	7647
xke		Kereho	7648
xkf		Khengkha	7649
xkg		Kagoro	7650
xkh		Karahawyana	7651
xki		Kenyan Sign Language	7652
xkj		Kajali	7653
xkk		Kaco'	7654
xkl		Mainstream Kenyah	7655
xkn		Kayan River Kayan	7656
xko		Kiorr	7657
xkp		Kabatei	7658
xkq		Koroni	7659
xkr		Xakriabá	7660
xks		Kumbewaha	7661
xkt		Kantosi	7662
xku		Kaamba	7663
xkv		Kgalagadi	7664
xkw		Kembra	7665
xkx		Karore	7666
xky		Uma' Lasan	7667
xkz		Kurtokha	7668
xla		Kamula	7669
xlb		Loup B	7670
xlc		Lycian	7671
xld		Lydian	7672
xle		Lemnian	7673
xlg		Ligurian (Ancient)	7674
xli		Liburnian	7675
xln		Alanic	7676
xlo		Loup A	7677
xlp		Lepontic	7678
xls		Lusitanian	7679
xlu		Cuneiform Luwian	7680
xly		Elymian	7681
xma		Mushungulu	7682
xmb		Mbonga	7683
xmc		Makhuwa-Marrevone	7684
xmd		Mbedam	7685
xme		Median	7686
xmf		Mingrelian	7687
xmg		Mengaka	7688
xmh		Kuku-Muminh	7689
xmj		Majera	7690
xmk		Ancient Macedonian	7691
xml		Malaysian Sign Language	7692
xmm		Manado Malay	7693
xmn		Manichaean Middle Persian	7694
xmo		Morerebi	7695
xmp		Kuku-Mu'inh	7696
xmq		Kuku-Mangk	7697
xmr		Meroitic	7698
xms		Moroccan Sign Language	7699
xmt		Matbat	7700
xmu		Kamu	7701
xmv		Antankarana Malagasy	7702
xmw		Tsimihety Malagasy	7703
xmx		Maden	7704
xmy		Mayaguduna	7705
xmz		Mori Bawah	7706
xna		Ancient North Arabian	7707
xnb		Kanakanabu	7708
xng		Middle Mongolian	7709
xnh		Kuanhua	7710
xnn		Northern Kankanay	7711
xno		Anglo-Norman	7712
xnr		Kangri	7713
xns		Kanashi	7714
xnt		Narragansett	7715
xoc		O'chi'chi'	7716
xod		Kokoda	7717
xog		Soga	7718
xoi		Kominimung	7719
xok		Xokleng	7720
xom		Komo (Sudan)	7721
xon		Konkomba	7722
xoo		Xukurú	7723
xop		Kopar	7724
xor		Korubo	7725
xow		Kowaki	7726
xpc		Pecheneg	7727
xpe		Liberia Kpelle	7728
xpg		Phrygian	7729
xpi		Pictish	7730
xpk		Kulina Pano	7731
xpm		Pumpokol	7732
xpn		Kapinawá	7733
xpo		Pochutec	7734
xpp		Puyo-Paekche	7735
xpq		Mohegan-Pequot	7736
xpr		Parthian	7737
xps		Pisidian	7738
xpu		Punic	7739
xpy		Puyo	7740
xqa		Karakhanid	7741
xqt		Qatabanian	7742
xra		Krahô	7743
xrb		Eastern Karaboro	7744
xre		Kreye	7745
xri		Krikati-Timbira	7746
xrm		Armazic	7747
xrn		Arin	7748
xrr		Raetic	7749
xrt		Aranama-Tamique	7750
xru		Marriammu	7751
xrw		Karawa	7752
xsa		Sabaean	7753
xsb		Tinà Sambal	7754
xsc		Scythian	7755
xsd		Sidetic	7756
xse		Sempan	7757
xsh		Shamang	7758
xsi		Sio	7759
xsj		Subi	7760
xsl		South Slavey	7761
xsm		Kasem	7762
xsn		Sanga (Nigeria)	7763
xso		Solano	7764
xsp		Silopi	7765
xsq		Makhuwa-Saka	7766
xsr		Sherpa	7767
xss		Assan	7768
xsu		Sanumá	7769
xsv		Sudovian	7770
xsy		Saisiyat	7771
xta		Alcozauca Mixtec	7772
xtb		Chazumba Mixtec	7773
xtc		Katcha-Kadugli-Miri	7774
xtd		Diuxi-Tilantongo Mixtec	7775
xte		Ketengban	7776
xtg		Transalpine Gaulish	7777
xti		Sinicahua Mixtec	7778
xtj		San Juan Teita Mixtec	7779
xtl		Tijaltepec Mixtec	7780
xtm		Magdalena Peñasco Mixtec	7781
xtn		Northern Tlaxiaco Mixtec	7782
xto		Tokharian A	7783
xtp		San Miguel Piedras Mixtec	7784
xtq		Tumshuqese	7785
xtr		Early Tripuri	7786
xts		Sindihui Mixtec	7787
xtt		Tacahua Mixtec	7788
xtu		Cuyamecalco Mixtec	7789
xtw		Tawandê	7790
xty		Yoloxochitl Mixtec	7791
xtz		Tasmanian	7792
xua		Alu Kurumba	7793
xub		Betta Kurumba	7794
xug		Kunigami	7795
xuj		Jennu Kurumba	7796
xum		Umbrian	7797
xuo		Kuo	7798
xup		Upper Umpqua	7799
xur		Urartian	7800
xut		Kuthant	7801
xuu		Kxoe	7802
xve		Venetic	7803
xvi		Kamviri	7804
xvn		Vandalic	7805
xvo		Volscian	7806
xvs		Vestinian	7807
xwa		Kwaza	7808
xwc		Woccon	7809
xwe		Xwela Gbe	7810
xwg		Kwegu	7811
xwl		Western Xwla Gbe	7812
xwo		Written Oirat	7813
xwr		Kwerba Mamberamo	7814
xxb		Boro (Ghana)	7815
xxk		Ke'o	7816
xxr		Koropó	7817
xxt		Tambora	7818
xyl		Yalakalore	7819
xzh		Zhang-Zhung	7820
xzm		Zemgalian	7821
xzp		Ancient Zapotec	7822
yaa		Yaminahua	7823
yab		Yuhup	7824
yac		Pass Valley Yali	7825
yad		Yagua	7826
yae		Pumé	7827
yaf		Yaka (Democratic Republic of Congo)	7828
yag		Yámana	7829
yah		Yazgulyam	7830
yai		Yagnobi	7831
yaj		Banda-Yangere	7832
yak		Yakama	7833
yal		Yalunka	7834
yam		Yamba	7835
yan		Mayangna	7836
yao		Yao	7837
yap		Yapese	7838
yaq		Yaqui	7839
yar		Yabarana	7840
yas		Nugunu (Cameroon)	7841
yat		Yambeta	7842
yau		Yuwana	7843
yav		Yangben	7844
yaw		Yawalapití	7845
yax		Yauma	7846
yay		Agwagwune	7847
yaz		Lokaa	7848
yba		Yala	7849
ybb		Yemba	7850
ybd		Yangbye	7851
ybe		West Yugur	7852
ybh		Yakha	7853
ybi		Yamphu	7854
ybj		Hasha	7855
ybk		Bokha	7856
ybl		Yukuben	7857
ybm		Yaben	7858
ybn		Yabaâna	7859
ybo		Yabong	7860
ybx		Yawiyo	7861
yby		Yaweyuha	7862
ych		Chesu	7863
ycl		Lolopo	7864
ycn		Yucuna	7865
ycp		Chepya	7866
ydd		Eastern Yiddish	7867
yde		Yangum Dey	7868
ydg		Yidgha	7869
ydk		Yoidik	7870
yds		Yiddish Sign Language	7871
yea		Ravula	7872
yec		Yeniche	7873
yee		Yimas	7874
yei		Yeni	7875
yej		Yevanic	7876
yel		Yela	7877
yen		Yendang	7878
yer		Tarok	7879
yes		Yeskwa	7880
yet		Yetfa	7881
yeu		Yerukula	7882
yev		Yapunda	7883
yey		Yeyi	7884
ygl		Yangum Gel	7885
ygm		Yagomi	7886
ygp		Gepo	7887
ygr		Yagaria	7888
ygw		Yagwoia	7889
yha		Baha Buyang	7890
yhd		Judeo-Iraqi Arabic	7891
yhl		Hlepho Phowa	7892
yia		Yinggarda	7893
yid	yi	Yiddish	7894
yif		Ache	7895
yig		Wusa Nasu	7896
yih		Western Yiddish	7897
yii		Yidiny	7898
yij		Yindjibarndi	7899
yik		Dongshanba Lalo	7900
yil		Yindjilandji	7901
yim		Yimchungru Naga	7902
yin		Yinchia	7903
yip		Pholo	7904
yiq		Miqie	7905
yir		North Awyu	7906
yis		Yis	7907
yit		Eastern Lalu	7908
yiu		Awu	7909
yiv		Northern Nisu	7910
yix		Axi Yi	7911
yiy		Yir Yoront	7912
yiz		Azhe	7913
yka		Yakan	7914
ykg		Northern Yukaghir	7915
yki		Yoke	7916
ykk		Yakaikeke	7917
ykl		Khlula	7918
ykm		Kap	7919
yko		Yasa	7920
ykr		Yekora	7921
ykt		Kathu	7922
yky		Yakoma	7923
yla		Yaul	7924
ylb		Yaleba	7925
yle		Yele	7926
ylg		Yelogu	7927
yli		Angguruk Yali	7928
yll		Yil	7929
ylm		Limi	7930
yln		Langnian Buyang	7931
ylo		Naluo Yi	7932
ylr		Yalarnnga	7933
ylu		Aribwaung	7934
yly		Nyâlayu	7935
yma		Yamphe	7936
ymb		Yambes	7937
ymc		Southern Muji	7938
ymd		Muda	7939
yme		Yameo	7940
ymg		Yamongeri	7941
ymh		Mili	7942
ymi		Moji	7943
ymk		Makwe	7944
yml		Iamalele	7945
ymm		Maay	7946
ymn		Yamna	7947
ymo		Yangum Mon	7948
ymp		Yamap	7949
ymq		Qila Muji	7950
ymr		Malasar	7951
yms		Mysian	7952
ymt		Mator-Taygi-Karagas	7953
ymx		Northern Muji	7954
ymz		Muzi	7955
yna		Aluo	7956
ynd		Yandruwandha	7957
yne		Lang'e	7958
yng		Yango	7959
ynh		Yangho	7960
ynk		Naukan Yupik	7961
ynl		Yangulam	7962
ynn		Yana	7963
yno		Yong	7964
yns		Yansi	7965
ynu		Yahuna	7966
yob		Yoba	7967
yog		Yogad	7968
yoi		Yonaguni	7969
yok		Yokuts	7970
yol		Yola	7971
yom		Yombe	7972
yon		Yonggom	7973
yor	yo	Yoruba	7974
yos		Yos	7975
yox		Yoron	7976
yoy		Yoy	7977
ypa		Phala	7978
ypb		Labo Phowa	7979
ypg		Phola	7980
yph		Phupha	7981
ypm		Phuma	7982
ypn		Ani Phowa	7983
ypo		Alo Phola	7984
ypp		Phupa	7985
ypz		Phuza	7986
yra		Yerakai	7987
yrb		Yareba	7988
yre		Yaouré	7989
yri		Yarí	7990
yrk		Nenets	7991
yrl		Nhengatu	7992
yrn		Yerong	7993
yrs		Yarsun	7994
yrw		Yarawata	7995
ysc		Yassic	7996
ysd		Samatao	7997
ysl		Yugoslavian Sign Language	7998
ysn		Sani	7999
yso		Nisi (China)	8000
ysp		Southern Lolopo	8001
ysr		Sirenik Yupik	8002
yss		Yessan-Mayo	8003
ysy		Sanie	8004
yta		Talu	8005
ytl		Tanglang	8006
ytp		Thopho	8007
ytw		Yout Wam	8008
yua		Yucateco	8009
yub		Yugambal	8010
yuc		Yuchi	8011
yud		Judeo-Tripolitanian Arabic	8012
yue		Yue Chinese	8013
yuf		Havasupai-Walapai-Yavapai	8014
yug		Yug	8015
yui		Yurutí	8016
yuj		Karkar-Yuri	8017
yuk		Yuki	8018
yul		Yulu	8019
yum		Quechan	8020
yun		Bena (Nigeria)	8021
yup		Yukpa	8022
yuq		Yuqui	8023
yur		Yurok	8024
yut		Yopno	8025
yuu		Yugh	8026
yuw		Yau (Morobe Province)	8027
yux		Southern Yukaghir	8028
yuy		East Yugur	8029
yuz		Yuracare	8030
yva		Yawa	8031
yvt		Yavitero	8032
ywa		Kalou	8033
ywl		Western Lalu	8034
ywn		Yawanawa	8035
ywq		Wuding-Luquan Yi	8036
ywr		Yawuru	8037
ywt		Xishanba Lalo	8038
ywu		Wumeng Nasu	8039
yww		Yawarawarga	8040
yyu		Yau (Sandaun Province)	8041
yyz		Ayizi	8042
yzg		E'ma Buyang	8043
yzk		Zokhuo	8044
zaa		Sierra de Juárez Zapotec	8045
zab		San Juan Guelavía Zapotec	8046
zac		Ocotlán Zapotec	8047
zad		Cajonos Zapotec	8048
zae		Yareni Zapotec	8049
zaf		Ayoquesco Zapotec	8050
zag		Zaghawa	8051
zah		Zangwal	8052
zai		Isthmus Zapotec	8053
zaj		Zaramo	8054
zak		Zanaki	8055
zal		Zauzou	8056
zam		Miahuatlán Zapotec	8057
zao		Ozolotepec Zapotec	8058
zap		Zapotec	8059
zaq		Aloápam Zapotec	8060
zar		Rincón Zapotec	8061
zas		Santo Domingo Albarradas Zapotec	8062
zat		Tabaa Zapotec	8063
zau		Zangskari	8064
zav		Yatzachi Zapotec	8065
zaw		Mitla Zapotec	8066
zax		Xadani Zapotec	8067
zay		Zayse-Zergulla	8068
zaz		Zari	8069
zbc		Central Berawan	8070
zbe		East Berawan	8071
zbl		Blissymbols	8072
zbt		Batui	8073
zbw		West Berawan	8074
zca		Coatecas Altas Zapotec	8075
zch		Central Hongshuihe Zhuang	8076
zdj		Ngazidja Comorian	8077
zea		Zeeuws	8078
zeg		Zenag	8079
zeh		Eastern Hongshuihe Zhuang	8080
zen		Zenaga	8081
zga		Kinga	8082
zgb		Guibei Zhuang	8083
zgm		Minz Zhuang	8084
zgn		Guibian Zhuang	8085
zgr		Magori	8086
zha	za	Zhuang	8087
zhb		Zhaba	8088
zhd		Dai Zhuang	8089
zhi		Zhire	8090
zhn		Nong Zhuang	8091
zho	zh	Chinese	8092
zhw		Zhoa	8093
zia		Zia	8094
zib		Zimbabwe Sign Language	8095
zik		Zimakani	8096
zim		Mesme	8097
zin		Zinza	8098
zir		Ziriya	8099
ziw		Zigula	8100
ziz		Zizilivakan	8101
zka		Kaimbulawa	8102
zkb		Koibal	8103
zkg		Koguryo	8104
zkh		Khorezmian	8105
zkk		Karankawa	8106
zko		Kott	8107
zkp		São Paulo Kaingáng	8108
zkr		Zakhring	8109
zkt		Kitan	8110
zku		Kaurna	8111
zkv		Krevinian	8112
zkz		Khazar	8113
zlj		Liujiang Zhuang	8114
zlm		Malay (individual language)	8115
zln		Lianshan Zhuang	8116
zlq		Liuqian Zhuang	8117
zma		Manda (Australia)	8118
zmb		Zimba	8119
zmc		Margany	8120
zmd		Maridan	8121
zme		Mangerr	8122
zmf		Mfinu	8123
zmg		Marti Ke	8124
zmh		Makolkol	8125
zmi		Negeri Sembilan Malay	8126
zmj		Maridjabin	8127
zmk		Mandandanyi	8128
zml		Madngele	8129
zmm		Marimanindji	8130
zmn		Mbangwe	8131
zmo		Molo	8132
zmp		Mpuono	8133
zmq		Mituku	8134
zmr		Maranunggu	8135
zms		Mbesa	8136
zmt		Maringarr	8137
zmu		Muruwari	8138
zmv		Mbariman-Gudhinma	8139
zmw		Mbo (Democratic Republic of Congo)	8140
zmx		Bomitaba	8141
zmy		Mariyedi	8142
zmz		Mbandja	8143
zna		Zan Gula	8144
zne		Zande (individual language)	8145
zng		Mang	8146
znk		Manangkari	8147
zns		Mangas	8148
zoc		Copainalá Zoque	8149
zoh		Chimalapa Zoque	8150
zom		Zou	8151
zoo		Asunción Mixtepec Zapotec	8152
zoq		Tabasco Zoque	8153
zor		Rayón Zoque	8154
zos		Francisco León Zoque	8155
zpa		Lachiguiri Zapotec	8156
zpb		Yautepec Zapotec	8157
zpc		Choapan Zapotec	8158
zpd		Southeastern Ixtlán Zapotec	8159
zpe		Petapa Zapotec	8160
zpf		San Pedro Quiatoni Zapotec	8161
zpg		Guevea De Humboldt Zapotec	8162
zph		Totomachapan Zapotec	8163
zpi		Santa María Quiegolani Zapotec	8164
zpj		Quiavicuzas Zapotec	8165
zpk		Tlacolulita Zapotec	8166
zpl		Lachixío Zapotec	8167
zpm		Mixtepec Zapotec	8168
zpn		Santa Inés Yatzechi Zapotec	8169
zpo		Amatlán Zapotec	8170
zpp		El Alto Zapotec	8171
zpq		Zoogocho Zapotec	8172
zpr		Santiago Xanica Zapotec	8173
zps		Coatlán Zapotec	8174
zpt		San Vicente Coatlán Zapotec	8175
zpu		Yalálag Zapotec	8176
zpv		Chichicapan Zapotec	8177
zpw		Zaniza Zapotec	8178
zpx		San Baltazar Loxicha Zapotec	8179
zpy		Mazaltepec Zapotec	8180
zpz		Texmelucan Zapotec	8181
zqe		Qiubei Zhuang	8182
zra		Kara (Korea)	8183
zrg		Mirgan	8184
zrn		Zerenkel	8185
zro		Záparo	8186
zrp		Zarphatic	8187
zrs		Mairasi	8188
zsa		Sarasira	8189
zsk		Kaskean	8190
zsl		Zambian Sign Language	8191
zsm		Standard Malay	8192
zsr		Southern Rincon Zapotec	8193
zsu		Sukurum	8194
zte		Elotepec Zapotec	8195
ztg		Xanaguía Zapotec	8196
ztl		Lapaguía-Guivini Zapotec	8197
ztm		San Agustín Mixtepec Zapotec	8198
ztn		Santa Catarina Albarradas Zapotec	8199
ztp		Loxicha Zapotec	8200
ztq		Quioquitani-Quierí Zapotec	8201
zts		Tilquiapan Zapotec	8202
ztt		Tejalapan Zapotec	8203
ztu		Güilá Zapotec	8204
ztx		Zaachila Zapotec	8205
zty		Yatee Zapotec	8206
zua		Zeem	8207
zuh		Tokano	8208
zul	zu	Zulu	8209
zum		Kumzari	8210
zun		Zuni	8211
zuy		Zumaya	8212
zwa		Zay	8213
zxx		No linguistic content	8214
zyb		Yongbei Zhuang	8215
zyg		Yang Zhuang	8216
zyj		Youjiang Zhuang	8217
zyn		Yongnan Zhuang	8218
zyp		Zyphe	8219
zza		Zaza	8220
zzj		Zuojiang Zhuang	8221
grc		Ancient Greek	8222
fro		Old French	8223
\.


--
-- Name: iso_639_language_codes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.iso_639_language_codes_id_seq', 1, false);


--
-- Data for Name: pro_activity; Type: TABLE DATA; Schema: public; Owner: cofktanya
--

COPY public.pro_activity (id, activity_type_id, activity_name, activity_description, date_type, date_from_year, date_from_month, date_from_day, date_from_uncertainty, date_to_year, date_to_month, date_to_day, date_to_uncertainty, notes_used, additional_notes, creation_timestamp, creation_user, change_timestamp, change_user, event_label) FROM stdin;
\.


--
-- Data for Name: pro_activity_relation; Type: TABLE DATA; Schema: public; Owner: cofktanya
--

COPY public.pro_activity_relation (id, meta_activity_id, filename, spreadsheet_row, combined_spreadsheet_row) FROM stdin;
\.


--
-- Data for Name: pro_assertion; Type: TABLE DATA; Schema: public; Owner: cofktanya
--

COPY public.pro_assertion (id, assertion_type, assertion_id, source_id, source_description, change_timestamp) FROM stdin;
\.


--
-- Name: pro_id_activity; Type: SEQUENCE SET; Schema: public; Owner: cofktanya
--

SELECT pg_catalog.setval('public.pro_id_activity', 45, false);


--
-- Name: pro_id_activity_relation; Type: SEQUENCE SET; Schema: public; Owner: cofktanya
--

SELECT pg_catalog.setval('public.pro_id_activity_relation', 1, false);


--
-- Name: pro_id_assertion; Type: SEQUENCE SET; Schema: public; Owner: cofktanya
--

SELECT pg_catalog.setval('public.pro_id_assertion', 1, false);


--
-- Name: pro_id_location; Type: SEQUENCE SET; Schema: public; Owner: cofktanya
--

SELECT pg_catalog.setval('public.pro_id_location', 1, false);


--
-- Name: pro_id_primary_person; Type: SEQUENCE SET; Schema: public; Owner: cofktanya
--

SELECT pg_catalog.setval('public.pro_id_primary_person', 1, false);


--
-- Name: pro_id_relationship; Type: SEQUENCE SET; Schema: public; Owner: cofktanya
--

SELECT pg_catalog.setval('public.pro_id_relationship', 1, false);


--
-- Name: pro_id_role_in_activity; Type: SEQUENCE SET; Schema: public; Owner: cofktanya
--

SELECT pg_catalog.setval('public.pro_id_role_in_activity', 1, false);


--
-- Name: pro_id_textual_source; Type: SEQUENCE SET; Schema: public; Owner: cofktanya
--

SELECT pg_catalog.setval('public.pro_id_textual_source', 1, false);


--
-- Data for Name: pro_ingest_map_v2; Type: TABLE DATA; Schema: public; Owner: cofktanya
--

COPY public.pro_ingest_map_v2 (relationship, mapping, s_event_category, s_event_type, s_role, p_event_category, p_event_type, p_role) FROM stdin;
\.


--
-- Data for Name: pro_ingest_v8; Type: TABLE DATA; Schema: public; Owner: cofktanya
--

COPY public.pro_ingest_v8 (event_category, event_type, event_name, pp_i, pp_name, pp_role, sp_i, sp_name, sp_type, sp_role, df_year, df_month, df_day, df_uncertainty, dt_year, dt_month, dt_day, dt_uncertainty, date_type, location_i, location_detail, location_city, location_region, location_country, location_type, ts_abbrev, ts_detail, editor, noted_used, add_notes, filename, spreadsheet_row_id, combined_csv_row_id) FROM stdin;
\.


--
-- Data for Name: pro_ingest_v8_toreview; Type: TABLE DATA; Schema: public; Owner: cofktanya
--

COPY public.pro_ingest_v8_toreview (event_category, event_type, event_name, pp_i, pp_name, pp_role, sp_i, sp_name, sp_type, sp_role, df_year, df_month, df_day, df_uncertainty, dt_year, dt_month, dt_day, dt_uncertainty, date_type, location_i, location_detail, location_city, location_region, location_country, location_type, ts_abbrev, ts_detail, editor, noted_used, add_notes, filename, spreadsheet_row_id, combined_csv_row_id) FROM stdin;
\.


--
-- Data for Name: pro_location; Type: TABLE DATA; Schema: public; Owner: cofktanya
--

COPY public.pro_location (id, location_id, change_timestamp, activity_id) FROM stdin;
\.


--
-- Data for Name: pro_people_check; Type: TABLE DATA; Schema: public; Owner: cofktanya
--

COPY public.pro_people_check (person_name, iperson_id) FROM stdin;
\.


--
-- Data for Name: pro_primary_person; Type: TABLE DATA; Schema: public; Owner: cofktanya
--

COPY public.pro_primary_person (id, person_id, change_timestamp, activity_id) FROM stdin;
\.


--
-- Data for Name: pro_relationship; Type: TABLE DATA; Schema: public; Owner: cofktanya
--

COPY public.pro_relationship (id, subject_id, subject_type, subject_role_id, relationship_id, object_id, object_type, object_role_id, change_timestamp, activity_id) FROM stdin;
\.


--
-- Data for Name: pro_role_in_activity; Type: TABLE DATA; Schema: public; Owner: cofktanya
--

COPY public.pro_role_in_activity (id, entity_type, entity_id, role_id, change_timestamp, activity_id) FROM stdin;
\.


--
-- Data for Name: pro_textual_source; Type: TABLE DATA; Schema: public; Owner: cofktanya
--

COPY public.pro_textual_source (id, author, title, "chapterArticleTitle", "volumeSeriesNumber", "issueNumber", "pageNumber", editor, "placePublication", "datePublication", "urlResource", abbreviation, "fullBibliographicDetails", edition, "reprintFacsimile", repository, creation_user, creation_timestamp, change_user, change_timestamp) FROM stdin;
\.


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

