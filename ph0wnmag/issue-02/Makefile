# pip install pandoc-latex-environment
# use python < 3.10 (3.10 is not supported)

all : ph0wnmag.pdf

ph0wnmag.pdf: *.md
	pandoc *.md --template ./eisvogel.latex --lua-filter="./columns.lua" -o $@ --from markdown --filter pandoc-latex-environment --listings --toc

clean:
	rm -f ph0wnmag.pdf
