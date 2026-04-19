import os
import re

def fix_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # Find methods that are marked : void but return something
    # This regex is a bit simplistic but should catch many cases
    pattern = r'(public|protected|private)\s+function\s+(\w+)\s*\((.*?)\)\s*:\s*void\s*\{'
    
    matches = list(re.finditer(pattern, content, re.DOTALL))
    if not matches:
        return False

    new_content = content
    offset = 0
    modified = False

    for match in matches:
        start_pos = match.start() + offset
        end_pos = match.end() + offset
        func_name = match.group(2)
        
        # Find the end of the function (counting braces)
        brace_count = 1
        pos = end_pos
        func_body = ""
        while brace_count > 0 and pos < len(new_content):
            if new_content[pos] == '{':
                brace_count += 1
            elif new_content[pos] == '}':
                brace_count -= 1
            func_body += new_content[pos]
            pos += 1
        
        # Check if the body contains "return value;"
        if re.search(r'return\s+[^;]+;', func_body):
            # It returns a value! Let's change : void to : mixed or try to guess.
            # For WordPress filters/actions, mixed or array or string is common.
            # Using mixed is safer if we don't know.
            old_decl = match.group(0)
            new_decl = old_decl.replace(': void', ': mixed')
            new_content = new_content[:start_pos] + new_decl + new_content[end_pos:]
            offset += len(new_decl) - len(old_decl)
            modified = True
            print(f"Fixed {func_name} in {filepath}")

    if modified:
        with open(filepath, 'w') as f:
            f.write(new_content)
    return modified

for root, dirs, files in os.walk('includes'):
    for file in files:
        if file.endswith('.php'):
            fix_file(os.path.join(root, file))
