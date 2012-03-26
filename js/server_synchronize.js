/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * for server_synchronize.php 
 *
 */

// Global variable row_class is set to even
var row_class = 'even';

/**
* Generates a row dynamically in the differences table displaying
* the complete statistics of difference in  table like number of
* rows to be updated, number of rows to be inserted, number of
* columns to be added, number of columns to be removed, etc.
*
* @param  index         index of matching table
* @param  update_size   number of rows/column to be updated
* @param  insert_size   number of rows/coulmns to be inserted
* @param  remove_size   number of columns to be removed
* @param  insert_index  number of indexes to be inserted
* @param  remove_index  number of indexes to be removed
* @param  img_obj       image object
* @param  table_name    name of the table
*/

function showDetails(i, update_size, insert_size, remove_size, insert_index, remove_index, img_obj, table_name)
{
    // a jQuery object
    var $img = $(img_obj);

    $img.toggleClass('selected');

    // The image source is changed when the showDetails function is called.
    if ($img.hasClass('selected')) {
        if ($img.hasClass('struct_img')) {
            $img.attr('src', pmaThemeImage + 'new_struct_selected.png');
        }
        if ($img.hasClass('data_img')) {
            $img.attr('src', pmaThemeImage + 'new_data_selected.png');
        }
    } else {
        if ($img.hasClass('struct_img')) {
            $img.attr('src', pmaThemeImage + 'new_struct.png');
        }
        if ($img.hasClass('data_img')) {
            $img.attr('src', pmaThemeImage + 'new_data.png');
        }
    }

    var div = document.getElementById("list");
    var table = div.getElementsByTagName("table")[0];
    var table_body = table.getElementsByTagName("tbody")[0];

    //Global variable row_class is being used
    if (row_class == 'even') {
        row_class = 'odd';
    } else {
        row_class = 'even';
    }
    // If the red or green button against a table name is pressed then append a new row to show the details of differences of this table.
    if ($img.hasClass('selected')) {
        var newRow = document.createElement("tr");
        newRow.setAttribute("class", row_class);
        newRow.className = row_class;
        // Id assigned to this row element is same as the index of this table name in the  matching_tables/source_tables_uncommon array
        newRow.setAttribute("id" , i);

        var table_name_cell = document.createElement("td");
        table_name_cell.align = "center";
        table_name_cell.innerHTML = table_name ;

        newRow.appendChild(table_name_cell);

        var create_table = document.createElement("td");
        create_table.align = "center";

        var add_cols = document.createElement("td");
        add_cols.align = "center";

        var remove_cols = document.createElement("td");
        remove_cols.align = "center";

        var alter_cols = document.createElement("td");
        alter_cols.align = "center";

        var add_index = document.createElement("td");
        add_index.align = "center";

        var delete_index = document.createElement("td");
        delete_index.align = "center";

        var update_rows = document.createElement("td");
        update_rows.align = "center";

        var insert_rows = document.createElement("td");
        insert_rows.align = "center";

        var tick_image = document.createElement("img");
        tick_image.src = PMA_getImage('s_success.png').attr('src');
        tick_image.className = PMA_getImage('s_success.png').attr('class');

        if (update_size == '' && insert_size == '' && remove_size == '') {
          /**
          This is the case when the table needs to be created in target database.
          */
            create_table.appendChild(tick_image);
            add_cols.innerHTML = "--";
            remove_cols.innerHTML = "--";
            alter_cols.innerHTML = "--";
            delete_index.innerHTML = "--";
            add_index.innerHTML = "--";
            update_rows.innerHTML = "--";
            insert_rows.innerHTML = "--";

            newRow.appendChild(create_table);
            newRow.appendChild(add_cols);
            newRow.appendChild(remove_cols);
            newRow.appendChild(alter_cols);
            newRow.appendChild(delete_index);
            newRow.appendChild(add_index);
            newRow.appendChild(update_rows);
            newRow.appendChild(insert_rows);

        } else if (update_size == '' && remove_size == '') {
           /**
           This is the case when data difference is displayed in the
           table which is present in source but absent from target database
          */
            create_table.innerHTML = "--";
            add_cols.innerHTML = "--";
            remove_cols.innerHTML = "--";
            alter_cols.innerHTML = "--";
            add_index.innerHTML = "--";
            delete_index.innerHTML = "--";
            update_rows.innerHTML = "--";
            insert_rows.innerHTML = insert_size;

            newRow.appendChild(create_table);
            newRow.appendChild(add_cols);
            newRow.appendChild(remove_cols);
            newRow.appendChild(alter_cols);
            newRow.appendChild(delete_index);
            newRow.appendChild(add_index);
            newRow.appendChild(update_rows);
            newRow.appendChild(insert_rows);

        } else if (remove_size == '') {
            /**
             This is the case when data difference between matching_tables is displayed.
            */
            create_table.innerHTML = "--";
            add_cols.innerHTML = "--";
            remove_cols.innerHTML = "--";
            alter_cols.innerHTML = "--";
            add_index.innerHTML = "--";
            delete_index.innerHTML = "--";
            update_rows.innerHTML = update_size;
            insert_rows.innerHTML = insert_size;

            newRow.appendChild(create_table);
            newRow.appendChild(add_cols);
            newRow.appendChild(remove_cols);
            newRow.appendChild(alter_cols);
            newRow.appendChild(delete_index);
            newRow.appendChild(add_index);
            newRow.appendChild(update_rows);
            newRow.appendChild(insert_rows);

        } else {
            /**
            This is the case when structure difference between matching_tables id displayed
            */
            create_table.innerHTML = "--";
            add_cols.innerHTML = insert_size;
            remove_cols.innerHTML = remove_size;
            alter_cols.innerHTML = update_size;
            delete_index.innerHTML = remove_index;
            add_index.innerHTML = insert_index;
            update_rows.innerHTML = "--";
            insert_rows.innerHTML = "--";

            newRow.appendChild(create_table);
            newRow.appendChild(add_cols);
            newRow.appendChild(remove_cols);
            newRow.appendChild(alter_cols);
            newRow.appendChild(delete_index);
            newRow.appendChild(add_index);
            newRow.appendChild(update_rows);
            newRow.appendChild(insert_rows);
        }
        table_body.appendChild(newRow);

    } else {
      //The case when the row showing the details need to be removed from the table i.e. the difference button is deselected now.
        var table_rows = table_body.getElementsByTagName("tr");
        var j;
        var index = 0;
        for (j=0; j < table_rows.length; j++)
        {
            if (table_rows[j].id == i) {
                index = j;
                table_rows[j].parentNode.removeChild(table_rows[j]);
            }
        }
        //The table row css is being adjusted. Class "odd" for odd rows and "even" for even rows should be maintained.
        for(index = 0; index < table_rows.length; index++)
        {
            row_class_element = table_rows[index].getAttribute('class');
            if (row_class_element == "even") {
                table_rows[index].setAttribute("class","odd");  // for Mozilla firefox
                table_rows[index].className = "odd";            // for IE browser
            } else {
                table_rows[index].setAttribute("class","even"); // for Mozilla firefox
                table_rows[index].className = "even";           // for IE browser
            }
        }
    }
}

/**
 * Generates the URL containing the list of selected table ids for synchronization and
 * a variable checked for confirmation of deleting previous rows from target tables
 *
 * @param   token   the token generated for each PMA form
 *
 */
function ApplySelectedChanges(token)
{
    /**
     Append the token at the beginning of the query string followed by
    Table_ids that shows that "Apply Selected Changes" button is pressed
    */
    var params = {
        token: $('#synchronize_form input[name=token]').val(),
        server: $('#synchronize_form input[name=server]').val(),
        checked: $('#delete_rows').prop('checked') ? 'true' : 'false',
        Table_ids: 1
    };
    var $rows = $('#list tbody tr');
    for(var i = 0; i < $rows.length; i++) {
        params[i] = $($rows[i]).attr('id');
    }

    //Appending the token and list of table ids in the URL
    location.href += '?' + $.param(params);
}


/**
 * Validates a partial form (source part or target part) 
 *
 * @param   which   'src' or 'trg' 
 * @return  boolean  whether the partial form is valid 
 *
 */
function validateSourceOrTarget(which) 
{
    var partial_form_is_ok = true;

    if ($("#" + which + "_type").val() != 'cur') {
        // did not choose "current connection"
        if ($("input[name='" + which + "_username']").val() == ''
            || $("input[name='" + which + "_pass']").val() == ''
            || $("input[name='" + which + "_db']").val() == ''
            // must have at least a host or a socket
            || ($("input[name='" + which + "_host']").val() == ''
                && $("input[name='" + which + "_socket']").val() == '')    
            // port can be empty
                ) {
            partial_form_is_ok = false; 
        }
    }
    return partial_form_is_ok;
} 
/**
* Displays an error message if any text field
* is left empty other than the port field, unless
* we are dealing with the "current connection" choice
*
* @return  boolean  whether the form is valid 
*/
function validateConnectionParams()
{
    var form_is_ok = true;

    if (! validateSourceOrTarget('src') || ! validateSourceOrTarget('trg')) {
        form_is_ok = false;
    }
    if (! form_is_ok) {
        alert(PMA_messages['strFormEmpty']);
    }
    return form_is_ok;
}

/**
 * Handles the dynamic display of form fields related to a server selector
 */

function hideOrDisplayServerFields($server_selector, selected_option)
{
    $tbody = $server_selector.closest('tbody');
    if (selected_option == 'cur') {
        $tbody.children('.current-server').css('display', '');
        $tbody.children('.remote-server').css('display', 'none');
    } else if (selected_option == 'rmt') {
        $tbody.children('.current-server').css('display', 'none');
        $tbody.children('.remote-server').css('display', '');
    } else {
        $tbody.children('.current-server').css('display', 'none');
        $tbody.children('.remote-server').css('display', '');
        var parts = selected_option.split('||||');
        $tbody.find('.server-host').val(parts[0]);
        $tbody.find('.server-port').val(parts[1]);
        $tbody.find('.server-socket').val(parts[2]);
        $tbody.find('.server-user').val(parts[3]);
        $tbody.find('.server-pass').val('');
        $tbody.find('.server-db').val(parts[4]);
    }
}

$(document).ready(function() {
    $('.server_selector').change(function(evt) {
        var selected_option = $(evt.target).val();
        hideOrDisplayServerFields($(evt.target), selected_option);
    });

    // initial display of the selectors
    $('.server_selector').each(function() {
        var selected_option = $(this).val();
        hideOrDisplayServerFields($(this), selected_option);
    });

    $('.struct_img').hover( 
        // pmaThemeImage comes from js/messages.php
        function() {
            // mouse enters the element
            var $img = $(this);
            $img.addClass('hover');
            if ($img.hasClass('selected')) {
                $img.attr('src', pmaThemeImage + 'new_struct_selected_hovered.png');
            } else {
                $img.attr('src', pmaThemeImage + 'new_struct_hovered.png');
            }
        },
        function() {
            // mouse leaves the element
            var $img = $(this);
            $img.removeClass('hover');
            if ($img.hasClass('selected')) {
                $img.attr('src', pmaThemeImage + 'new_struct_selected.png');
            } else {
                $img.attr('src', pmaThemeImage + 'new_struct.png');
            }
        }
    );

    $('.data_img').hover( 
        function() {
            // mouse enters the element
            var $img = $(this);
            $img.addClass('hover');
            if ($img.hasClass('selected')) {
                $img.attr('src', pmaThemeImage + 'new_data_selected_hovered.png');
            } else {
                $img.attr('src', pmaThemeImage + 'new_data_hovered.png');
            }
        },
        function() {
            // mouse leaves the element
            var $img = $(this);
            $img.removeClass('hover');
            if ($img.hasClass('selected')) {
                $img.attr('src', pmaThemeImage + 'new_data_selected.png');
            } else {
                $img.attr('src', pmaThemeImage + 'new_data.png');
            }
        }
    );

    $('#buttonGo').click(function(event) {
        if (! validateConnectionParams()) {
            event.preventDefault();
        }
    });
});
