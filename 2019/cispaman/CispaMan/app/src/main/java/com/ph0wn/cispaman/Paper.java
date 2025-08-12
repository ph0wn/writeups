package com.ph0wn.cispaman;

class Paper {
    public int title;
    public int authors;
    public int conf;
    public int year;
    public int citations;

    public Paper(int title, int authors, int conf, int year, int citations) {
        this.title = title; // left
        this.authors = authors; // top
        this.conf = conf; // right
        this.year = year; // bottom
        this.citations = citations; // resid
    }
}

