# Requires PHP
# 
# TODO - Check if pandoc is installed; error message otherwise
# if hash pandoc 2>/dev/null; then
#     we've got pandoc
#   else
#     echo 'Requires pandoc - http://pandoc.org/'
#   fi

echo -n 'Converting Trello JSON to Markdown... '
php jello.php _examples/jyTQlNXR.json > out_trello.md
echo 'Done!'

echo -n 'Converting Trello Markdown to Word... '
pandoc out_trello.md -f markdown -t docx --reference-docx jello_reference.docx -o out_doc.docx
echo 'Done!'

echo -n 'Converting Trello Markdown to JIRA Textile... '
pandoc out_trello.md -f markdown -t textile -o out_jira.txt
sed -i -e 's/\&quot;/\"/g' out_jira.txt 
sed -i -e 's/\&#45;/-/g' out_jira.txt 
sed -i -e 's/\&#43;/+/g' out_jira.txt 
sed -i -e 's/\&amp;/\&/g' out_jira.txt 
sed -i -e 's/\<hr \/\>/--------------------------------/g' out_jira.txt
echo 'Done!'