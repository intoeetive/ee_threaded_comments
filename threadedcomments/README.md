# Threaded Comments

Threaded Comment add-on for ExpressionEngine 3 enables nested comments on top of native Comment module and also provides custom comment fields.

## Requirements

ExpressionEngine 3.1.0 or later

## Settings

### Custom comment fields

To create custom field, select the Type (only 'text' and 'textarea' are currently supported), Field Label and Short Name. The field's short name will be used to display the field data within the comment and also as field name within comment form.

The data entered in custom comment fields can be seen on front-end only.

## Usage

### Display comments

Comment are being displayed via native `{exp:comment:entries}` tag with few special variables added.

#### Example Usage

```
<ul>
{exp:comment:entries sort="asc" limit="2" entry_id="1" paginate="bottom"}

{if thread_start}
<ul>
{/if}
<li>
<div class="comment-text">{comment}</div><br />
Date: {comment_date format="%Y-%m-%d %H:%i"}<br />
Author: {url_as_author}<br />
<a href="javascript:void(0)" class="reply" rel="{comment_id}">Reply to this comment</a> <a href="javascript:void(0)" class="quote reply" rel="{comment_id}">Quote and reply</a>
</li>
{if thread_end}
</ul>
{/if}
        
{paginate}
<li>Page {current_page} of {total_pages} pages {pagination_links}</li>
{/paginate}
{/exp:comment:entries}
</ul>
```

#### Parameters

All the same parameters that are used to display comments using `{exp:comment:entries}`


#### Variables

All variables available within `{exp:comment:entries}` can be used.

Additionally, few special variables are available:

##### `{if thread_start}...{/if}`

Displayed if the current comment has sub-comments under it (is starting the thread)

##### `{if thread_end}...{/if}`

Displayed when the thread need to be closed.

##### comment_total

Total number of comments for the entry ({total_results} is now resembling number of threads)

##### parent_id

ID of comment to which the current comment is reply. Zero for root level comments.

##### root_id

ID of zero-level ancestor comment of current thread. Zero for root level comments.

##### root_id

The nestedness level. Comments that are replies to entry have 0 level, replies to them level 1 etc.

##### my_custom_comment_field

Any custom comment fields created in add-on's control panel will be displayed using the data submitted with the comment.


### Post comments

The comment are being posted using native `{exp:comment:form}` tag.

#### Example Usage

##### Comment form
```
{exp:comment:form entry_id="1"}

{if logged_out}
        <label for="name">Name:</label> <input type="text" name="name" value="{name}" size="50" /><br />
        <label for="email">Email:</label> <input type="text" name="email" value="{email}" size="50" /><br />
        <label for="location">Location:</label> <input type="text" name="location" value="{location}" size="50" /><br />
        <label for="url">URL:</label> <input type="text" name="url" value="{url}" size="50" /><br />
{/if}

<label for="comment">Comment:</label><br />
<textarea name="comment" cols="70" rows="10">{comment}</textarea>

<label for="comment">Extra information:</label><br />
<textarea name="extra_info_custom_comment_field" cols="70" rows="10"></textarea>

<label><input type="checkbox" name="notify_thread" value="yes" {notify_thread} /> Notify me of comments in this thread?</label><br />

<input type="submit" name="submit" value="Submit" />

{/exp:comment:form}
```

##### JavaScript form manipulations

This code wil set proper parent_id and move the form around the page to post replied to comments. jQuery required.
```
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>

<p><a href="javascript:void(0)" class="reply" rel="0">Reply to entry</a></p> 

<style type="text/css">
#comment_form {display: none;}
</style>
<script type="text/javascript">
$(document).ready(function(){
  $('.reply').click(function() {
    $('#comment_form input[name=parent_id]').val($(this).attr('rel'));
    $('#comment_form').insertAfter( // Insert the comment form after...
    $(this)
    .parent() // The containing li tag
    );
    $('#comment_form').show();
  });
  $('.quote').click(function() {
    $('#comment_form textarea[name=comment]').val('[quote]'+ 
    $(this).parent().parent().find('.comment-text').text()+
    '[/quote]'
    );
  });
});
</script>
```

#### Variables

All variables available within `{exp:comment:form}` are available here, and also one extra:

##### notify_thread

If the user has "Send me emails When I post messages" checked in his profile, is is parsed as `checked="checked"`

#### Form fields

All fields available within `{exp:comment:form}` are available here, and also few additional fields:

##### parent_id

The field is being added to form's hidden fields automatically and you need to modify it when replying to comment. It should contain the ID of comment that user is replying to.

##### notify_thread

If the field is submitted with value `yes`, the user will be subscribed to email notification about replied in the thread he's posting to (new comments with same root_id)

##### my_custom_comment_field

Any custom comment fields created in add-on's control panel and available for submission with the form using their short names.


## Changelog

### 3.0.0

- Rewrite for ExpressionEngine 3.1.0

### 3.0.4

- Fix display of {url_as_author} variables on EE 4

## License

The purchase of the add-on grants you to use it on single production installation of ExpressionEngine. Should you be willing to use it on several production websites, you should purchase additional licenses. The full license agreement is available [here](http://www.intoeetive.com/docs/license.html)

## Support

Should you have any questions, please email support@intoeetive.com (for official support) or ask questions on EE StackOverflow (for community support)