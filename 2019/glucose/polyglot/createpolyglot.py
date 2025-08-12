#/usr/bin/env python3
import fitz
import struct
import argparse
# python3 createpolyglot.py --pdf ./analyses.pdf --zip ./thezip/hidden.zip

# Usage
def get_args():
    parser = argparse.ArgumentParser(description='Program to create PDF/ZIP polyglot for the challenge')
    parser.add_argument('-p', '--pdf', help='Input PDF file', nargs='+', action='store')
    parser.add_argument('-z', '--zip', help='Input ZIP file', nargs='+', action='store')

    args = parser.parse_args()
    return args

def read_file_data(filename):
    f = open(filename, 'rb')
    data = f.read()
    f.close()
    return data

def insert_zip(pdfname, zipname):
    zipdata = read_file_data(zipname)

    doc = fitz.open(pdfname)
    objNb = doc._getNewXref()
    doc._updateObject(objNb, '<<>>')
    doc._updateStream(objNb, zipdata+b'\0', new=True)

    print("[+] Inserted {0} in {1}".format(zipname, pdfname))
    return doc

def patch_eocd(input_filename, output_filename):
    filedata = read_file_data(input_filename)
    
    offset = filedata.rfind(b'PK\5\6') + 20
    length = len(filedata) - offset - 2
    
    f = open(output_filename, 'wb')
    f.write(filedata[:offset])
    f.write(struct.pack("<H", length))
    f.write(filedata[offset+2:])
    f.close()
    print("[+] Patched EOCD at offset={0}".format(offset))

# MAIN ---------------------
args = get_args()
if __name__ == '__main__':
    print("This program will overwrite {0}. Is this OK? (y/N) ".format(args.pdf[0]))
    answer = input()
    if answer == 'y':
        doc = insert_zip(args.pdf[0], args.zip[0])
        doc.save(doc.name, incremental=True, expand=255)
        print("[+] Saved PDF {0}".format(args.pdf[0]))
        patch_eocd(doc.name, doc.name)
else:
    print("[-] We didn't create the polyglot")
    
