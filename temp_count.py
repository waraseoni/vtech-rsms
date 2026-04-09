with open(r'c:\xampp\htdocs\vtech-rsms\db\vikram_db.sql', 'r', encoding='utf-8') as f:
    content = f.read()
lines = content.split('\n')
print('Total lines:', len(lines))
print('Tables:', content.count('CREATE TABLE'))
print('Inserts:', content.count('INSERT INTO'))
# Let's see some lines
for i in range(100, 150):
    if i < len(lines):
        print(f'{i+1:4}: {lines[i]}')