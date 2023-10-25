# Threaded Comments

Threaded Comment add-on for ExpressionEngine versions 3, 4, 5, 6 and 7 enables nested comments on top of native Comment module and also provides custom comment fields.

## #StandWithUkraine

This add-on is provided free of charge, however I would like to use the opportunity to ask you to support Ukraine in its fight for freedom and democracy. If you use this add-on in commercial projects, please consider donating to [Hospitaliers](https://www.hospitallers.life/needs-hospitallers#pay-pal-2), [Come Back Alive](https://savelife.in.ua/en/donate-en/) or any other charity that helps Ukraine.

## Requirements

ExpressionEngine 3.1.0 or later

## Installation

Copy the files into `/system/user/addons/threadedcomments/` folder on your server. Then wisit Add-Ons section in ExpressionEngine Control Panel and click Install next to Threaded Comments.

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

## MIT License

Copyright 2023 Yuri Salimovskiy

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
