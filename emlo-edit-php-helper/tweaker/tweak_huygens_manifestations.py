from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

# Setup
csv_file = "resources/Manifs_CHB_to_EMLO_Works_ToADD_2021.12.16a.csv"

id_sep = "-"
#id_sep = "-2"

skip_first_data_row = False

debugging = True
restrict = 0  # use 0 to restrict none.

id_name = 'iwork_id'
man_id_end = 'abcdefghijklmnopqrstuvwxyz'
work_id_manfestations = {}

##

def row_process( tweaker, row ) :
    work = tweaker.get_work_from_iwork_id(row[id_name])
    if not work:
        print("NOT FOUND:", row[id_name])
        return

    work_id = work['work_id']
    print("WORK ID:", work_id)

    # Manifestation record
    if (row['manifestation_type']):

        if work_id not in work_id_manfestations :
            work_id_manfestations[work_id] = 0
        else :
            work_id_manfestations[work_id] += 1

            if work_id_manfestations[work_id] >= len(man_id_end) :
                print( "Error: need more man id generation space")
                sys.exit()

        manifestation_id = ( str(work_id) + id_sep +
                             man_id_end[work_id_manfestations[work_id]] )
        print("  MANIFESTATION ID:", manifestation_id)
        tweaker.create_manifestation( manifestation_id,
            row['manifestation_type'],
            id_number_or_shelfmark=row['id_number_or_shelfmark']
        )
        # repository_id
        repository_id = int(row['repository_id'])
        tweaker.create_relationship_manifestation_in_repository( manifestation_id, repository_id)

        # iwork_id
        tweaker.create_relationship_manifestation_of_work( manifestation_id, work_id )

        # manifestation_notes
        if row['manifestation_notes'] :
            comment_id = tweaker.create_comment( row['manifestation_notes'] )
            tweaker.create_relationship_note_manifestation( comment_id, manifestation_id )

    # Printed Edition record
    if (row['manifestation_type_p']):

        if work_id not in work_id_manfestations :
            work_id_manfestations[work_id] = 0
        else :
            work_id_manfestations[work_id] += 1

            if work_id_manfestations[work_id] >= len(man_id_end) :
                print( "Error: need more man id generation space")
                sys.exit()

        manifestation_id = ( str(work_id) + id_sep
                           + man_id_end[work_id_manfestations[work_id]] )
        print("  MANIFESTATION ID P:", manifestation_id)
        tweaker.create_manifestation( manifestation_id,
            row['manifestation_type_p'],
            printed_edition_details=row['printed_edition_details']
        )

        # iwork_id
        tweaker.create_relationship_manifestation_of_work( manifestation_id, work_id )

        # manifestation_notes
        if row['printed_edition_notes'] :
            comment_id = tweaker.create_comment( row['printed_edition_notes'] )
            tweaker.create_relationship_note_manifestation( comment_id, manifestation_id )


def main() :

    tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
    tweaker.set_debug( debugging )

    if debugging:
        print( "Debug ON so no commit" )
        commit = False
    else :
        commit = do_commit()

    csv_rows = tweaker.get_csv_data( csv_file )

    if debugging and restrict:
        print( "Restricting rows to just", restrict)
        csv_rows = csv_rows[:restrict]

    count = countdown = len(csv_rows)
    for csv_row in csv_rows:

        if countdown == count and skip_first_data_row:
            continue

        print( str(countdown) + " of " + str(count), ":", csv_row[id_name] )

        row_process( tweaker, csv_row )

        countdown -= 1

    print()

    tweaker.print_audit()
    tweaker.commit_changes(commit)

    print( "Fini" )


def do_commit() :

    commit = ( raw_input("Commit changes to database (y/n): ") == "y")
    if commit:
        print( "COMMITTING changes to database." )
    else:
        print( "NOT committing changes to database." )

    return commit


if __name__ == '__main__':

    print( "Starting...")
    main()
    print( "...Finished")


