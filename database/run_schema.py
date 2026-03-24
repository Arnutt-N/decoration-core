"""
Execute schema + seed data on TiDB Cloud (decoration_core)
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')
import pymysql
import os

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))

# อ่าน credentials จาก secrets file หรือ environment variables
secrets_path = os.path.join(SCRIPT_DIR, '..', 'secrets', 'secret-keys.txt')
db_config = {}
if os.path.exists(secrets_path):
    with open(secrets_path, 'r') as f:
        for line in f:
            line = line.strip()
            if '=' in line and not line.startswith('#'):
                key, val = line.split('=', 1)
                db_config[key.strip()] = val.strip()

conn = pymysql.connect(
    host=db_config.get('MYSQL_HOST', os.environ.get('MYSQL_HOST', 'localhost')),
    port=int(db_config.get('MYSQL_PORT', os.environ.get('MYSQL_PORT', '4000'))),
    user=db_config.get('MYSQL_USER', os.environ.get('MYSQL_USER', 'root')),
    password=db_config.get('MYSQL_PASSWORD', os.environ.get('MYSQL_PASSWORD', '')),
    database=db_config.get('MYSQL_DATABASE', os.environ.get('MYSQL_DATABASE', 'decoration_core')),
    ssl={'ca': None},
    ssl_verify_cert=False,
    ssl_verify_identity=False,
    autocommit=True
)
cursor = conn.cursor()

def run_sql_file(filepath, label):
    print(f'\n=== {label} ===')
    with open(filepath, 'r', encoding='utf-8') as f:
        sql = f.read()

    # ลบ comment lines ก่อน split
    lines = [line for line in sql.split('\n') if not line.strip().startswith('--')]
    clean_sql = '\n'.join(lines)
    statements = [s.strip() for s in clean_sql.split(';') if s.strip()]

    for stmt in statements:
        if not stmt:
            continue
        try:
            cursor.execute(stmt)
            if 'CREATE TABLE' in stmt.upper():
                name = stmt.split('(')[0].split()[-1].strip('`').replace('IF NOT EXISTS ', '')
                print(f'  OK  CREATE {name}')
            elif 'INSERT' in stmt.upper():
                print(f'  OK  {cursor.rowcount} rows inserted')
        except Exception as e:
            err = str(e)[:100]
            print(f'  FAIL: {err}')

# Run schema
run_sql_file(os.path.join(SCRIPT_DIR, '01-decoration-schema.sql'), 'Creating tables')

# Run seed data
run_sql_file(os.path.join(SCRIPT_DIR, '02-seed-data.sql'), 'Inserting seed data')

# Verify
print('\n=== Verification ===')
cursor.execute("""
    SELECT TABLE_NAME FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = 'decoration_core' AND TABLE_TYPE = 'BASE TABLE'
    ORDER BY TABLE_NAME
""")
tables = cursor.fetchall()
print(f'Total tables: {len(tables)}')

for tbl in ['decoration_changpuak_levels', 'direk_levels', 'decoration_changpuak_criteria', 'roles', 'system_settings']:
    try:
        cursor.execute(f'SELECT COUNT(*) FROM {tbl}')
        cnt = cursor.fetchone()[0]
        print(f'  {tbl}: {cnt} rows')
    except Exception as e:
        print(f'  {tbl}: ERROR - {e}')

conn.close()
print('\nDone!')
