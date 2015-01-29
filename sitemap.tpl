<div class="titrePage">
  <h2>RV Sitemap</h2>
</div>


<form method="post" action="">

<fieldset>
<legend>{'Global'|@translate}</legend>
<p>
  <label for="filename">{'Filename'|@translate} <input type="input" name="filename" value="{$FILENAME}" /></label> <a href="{$U_FILENAME}">{$U_FILENAME}</a>
  <label for="gzip">Gzip<input type="checkbox" name="gzip" {$GZIP_CHECKED} /></label>
</p>
{foreach from=$specials item=special}
    <label><input type="checkbox" {$special.CHECKED} name="special_{$special.NAME}"/> {$special.LABEL} </label>
    <input type="input" size="4" name="special_prio_{$special.NAME}" value="{$special.PRIO}"/>
	<select name="special_freq_{$special.NAME}" >
	  {html_options values=$frequencies output=$frequenciesT selected=$special.FREQ}
	</select>
    <br/>
{/foreach}
</fieldset>

<fieldset>
<legend>{'Albums'|@translate}</legend>
<select style="width:500px" name="categories[]" multiple="multiple" size="15">
  {html_options options=$categories selected=$categories_selected}
</select>
<label for="prio_categories">{'Priority'|@translate} <input name="prio_categories" size="4" value="{$PRIO_CATEGORIES}" /></label>
<label for="freq_categories">{'Frequency'|@translate}
<select name="freq_categories" >
  {html_options values=$frequencies output=$frequenciesT selected=$freq_categories_selected}
</select>
</label>
</fieldset>

<fieldset>
<legend>{'Tags'|@translate}</legend>
<select style="width:200px" name="tags[]" multiple="multiple" size="15">
  {html_options options=$tags selected=$tags_selected}
</select>
<label for="prio_tags">{'Priority'|@translate} <input name="prio_tags" size="4" value="{$PRIO_TAGS}" /></label>
<label for="freq_tags">{'Frequency'|@translate}
<select name="freq_tags" >
  {html_options values=$frequencies output=$frequenciesT selected=$freq_tags_selected}
</select>
</label>
</fieldset>

<fieldset>
<legend>{'Recent photos'|@translate}</legend>
<input type="input" size="4" name="photo_count" value="{$PHOTO_COUNT}"/> {'Photos'|@translate}
&nbsp;&nbsp;&nbsp;
{'Multiple Size'|@translate}:
<select style="width:200px" name="selected_derivatives[]" multiple="multiple" size="5">
  {html_options options=$available_derivatives selected=$selected_derivatives}
</select>
</fieldset>


<p>
  <input type="submit" class="submit" value="{'Submit'|@translate}" name="submit" />
</p>

</form>
