import zipfile
import xml.etree.ElementTree as ET
import sys

def read_docx(path):
    document = zipfile.ZipFile(path)
    xml_content = document.read('word/document.xml')
    document.close()
    
    tree = ET.XML(xml_content)
    NAMESPACE = '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}'
    
    paragraphs = []
    for paragraph in tree.iter(NAMESPACE + 'p'):
        texts = [node.text for node in paragraph.iter(NAMESPACE + 't') if node.text]
        if texts:
            paragraphs.append("".join(texts))
            
    return "\n".join(paragraphs)

print(read_docx(sys.argv[1]))
