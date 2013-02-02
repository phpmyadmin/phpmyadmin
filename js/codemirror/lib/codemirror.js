var CodeMirror=function(){
    function F(b,e){
        function h(a){
            return a>=0&&a<t.size
        }
        function f(a){
            var c;
                a:{
                    var d=t;
                    for(a=a;;){
                        for(var g=0,i=d.children.length;g<i;++g){
                            var j=d.children[g],l=j.chunkSize();
                            if(a<l){
                                d=j;
                                break
                            }
                            a-=l
                        }
                        if(d.lines){
                            c=d.lines[a];
                            break a
                        }
                    }
                }
            return c
        }
        function k(a,c){
            ja=true;
            for(var d=c-a.height,g=a;g;g=g.parent)g.height+=d
        }
        function p(a){
            var c={
                line:0,
                ch:0
            };
    
            z(c,{
                line:t.size-1,
                ch:f(t.size-1).text.length
            },va(a),c,c);
            W=true
        }
        function q(){
            if(s.readOnly!="nocursor"){
                if(!Z){
                    s.onFocus&&s.onFocus(P);
                    Z=true;
                    if(K.className.search(/\bCodeMirror-focused\b/)==-1)K.className+=" CodeMirror-focused";
                    Na||Da()
                }
                pa();
                Zb()
            }
        }
        function B(){
            if(Z){
                s.onBlur&&s.onBlur(P);
                Z=false;
                K.className=K.className.replace(" CodeMirror-focused","")
            }
            clearInterval(Cb);
            setTimeout(function(){
                Z||(ca=null)
            },150)
        }
        function z(a,c,d,g,i){
            if($){
                var j=[];
                t.iter(a.line,c.line+1,function(l){
                    j.push(l.text)
                });
                for($.addChange(a.line,d.length,j);$.done.length>s.undoDepth;)$.done.shift()
            }
            ka(a,c,d,g,i)
        }
        function aa(a,c){
            var d=a.pop();
            if(d){
                var g=
                [],i=d.start+d.added;
                t.iter(d.start,i,function(l){
                    g.push(l.text)
                });
                c.push({
                    start:d.start,
                    added:d.old.length,
                    old:g
                });
                var j=I({
                    line:d.start+d.old.length-1,
                    ch:zc(g[g.length-1],d.old[d.old.length-1])
                });
                ka({
                    line:d.start,
                    ch:0
                },{
                    line:i-1,
                    ch:f(i-1).text.length
                },d.old,j,j);
                W=true
            }
        }
        function da(){
            aa($.done,$.undone)
        }
        function la(){
            aa($.undone,$.done)
        }
        function ka(a,c,d,g,i){
            var j=false,l=qa.length;
            s.lineWrapping||t.iter(a.line,c.line,function(v){
                if(v.text.length==l)return j=true
            });
            var o=c.line-a.line,m=f(a.line),
            r=f(c.line);
            if(m==r)if(d.length==1)m.replace(a.ch,c.ch,d[0]);
                else{
                    r=m.split(c.ch,d[d.length-1]);
                    m.replace(a.ch,null,d[0]);
                    m.fixMarkEnds(r);
                    for(var u=[],w=1,A=d.length-1;w<A;++w)u.push(wa.inheritMarks(d[w],m));
                    u.push(r);
                    t.insert(a.line+1,u)
                }else if(d.length==1){
                m.replace(a.ch,null,d[0]);
                r.replace(null,c.ch,"");
                m.append(r);
                t.remove(a.line+1,o)
            }else{
                u=[];
                m.replace(a.ch,null,d[0]);
                r.replace(null,c.ch,d[d.length-1]);
                m.fixMarkEnds(r);
                w=1;
                for(A=d.length-1;w<A;++w)u.push(wa.inheritMarks(d[w],m));
                o>1&&t.remove(a.line+
                    1,o-1);
                t.insert(a.line+1,u)
            }
            if(s.lineWrapping){
                var G=x.clientWidth/Db()-3;
                t.iter(a.line,a.line+d.length,function(v){
                    if(!v.hidden){
                        var S=Math.ceil(v.text.length/G)||1;
                        S!=v.height&&k(v,S)
                    }
                })
            }else{
                t.iter(a.line,w+d.length,function(v){
                    v=v.text;
                    if(v.length>l){
                        qa=v;
                        l=v.length;
                        ra=null;
                        j=false
                    }
                });
                if(j){
                    l=0;
                    qa="";
                    ra=null;
                    t.iter(0,t.size,function(v){
                        v=v.text;
                        if(v.length>l){
                            l=v.length;
                            qa=v
                        }
                    })
                }
            }
            m=[];
            o=d.length-o-1;
            w=0;
            for(r=ea.length;w<r;++w){
                u=ea[w];
                if(u<a.line)m.push(u);else u>c.line&&m.push(u+o)
            }
            w=a.line+
            Math.min(d.length,500);
            Ac(a.line,w);
            m.push(w);
            ea=m;
            Eb(100);
            L.push({
                from:a.line,
                to:c.line+1,
                diff:o
            });
            Fb={
                from:a,
                to:c,
                text:d
            };

            xa(g,i,n.from.line<=Math.min(c.line,c.line+o)?n.from.line:n.from.line+o,n.to.line<=Math.min(c.line,c.line+o)?n.to.line:n.to.line+o);
            V.style.height=t.height*fa()+2*D.offsetTop+"px"
        }
        function X(a,c,d){
            function g(j){
                if(ga(j,c))return j;
                if(!ga(d,j))return i;
                var l=j.line+a.length-(d.line-c.line)-1,o=j.ch;
                if(j.line==d.line)o+=a[a.length-1].length-(d.ch-(d.line==c.line?c.ch:0));
                return{
                    line:l,
                    ch:o
                }
            }
            c=I(c);
            d=d?I(d):c;
            a=va(a);
            var i;
            Oa(a,c,d,function(j){
                i=j;
                return{
                    from:g(n.from),
                    to:g(n.to)
                }
            });
            return i
        }
        function Q(a,c){
            Oa(va(a),n.from,n.to,function(d){
                return c=="end"?{
                    from:d,
                    to:d
                }:c=="start"?{
                    from:n.from,
                    to:n.from
                }:{
                    from:n.from,
                    to:d
                }
            })
        }
        function Oa(a,c,d,g){
            g=g({
                line:c.line+a.length-1,
                ch:a.length==1?a[0].length+c.ch:a[a.length-1].length
            });
            z(c,d,a,g.from,g.to)
        }
        function R(a,c){
            var d=a.line,g=c.line;
            if(d==g)return f(d).text.slice(a.ch,c.ch);
            var i=[f(d).text.slice(a.ch)];
            t.iter(d+1,g,function(j){
                i.push(j.text)
            });
            i.push(f(g).text.slice(0,c.ch));
            return i.join("\n")
        }
        function ma(){
            return R(n.from,n.to)
        }
        function pa(){
            Gb||Hb.set(Ea,function(){
                Ib();
                na();
                Z&&pa();
                Jb()
            })
        }
        function ha(a){
            function c(){
                Ib();
                var g=na();
                if(g&&a){
                    if(g=="moved"&&Fa[a]==null)Fa[a]=true;
                    if(g=="changed")Fa[a]=false
                }
                if(!g&&!d){
                    d=true;
                    Hb.set(80,c)
                }else{
                    Gb=false;
                    pa()
                }
                Jb()
            }
            var d=false;
            Gb=true;
            Hb.set(20,c)
        }
        function na(){
            function a(S,oa){
                for(var T=0;;){
                    var ya=d.indexOf("\n",T);
                    if(ya==-1||(d.charAt(ya-1)=="\r"?ya-1:ya)>=S)return{
                        line:oa,
                        ch:S-T
                    };
                
                    ++oa;
                    T=ya+1
                }
            }
            if(!(Na||!Z)){
                var c=false,d=C.value,g=bb(C);
                if(!g)return false;
                c=M.text!=d;
                var i=za,j=c||g.start!=M.start||g.end!=(i?M.start:M.end);
                if(!j&&!i)return false;
                if(c){
                    ca=za=null;
                    if(s.readOnly){
                        W=true;
                        return"changed"
                    }
                }
                var l=a(g.start,M.from),o=a(g.end,M.from);
                if(i){
                    var m=g.start==i.anchor?o:l;
                    o=ca?n.to:g.start==i.anchor?l:o;
                    if(n.inverted=ga(m,o)){
                        l=m;
                        o=o
                    }else{
                        za=null;
                        l=o;
                        o=m
                    }
                }
                if(l.line==o.line&&l.line==n.from.line&&l.line==n.to.line&&!ca)W=false;
                if(c){
                    m=0;
                    i=d.length;
                    for(var r=Math.min(i,M.text.length),
                        u,w=M.from,A=-1;m<r&&(u=d.charAt(m))==M.text.charAt(m);){
                        ++m;
                        if(u=="\n"){
                            w++;
                            A=m
                        }
                    }
                    r=A>-1?m-A:m;
                    for(var G=M.to-1,v=M.text.length;;){
                        u=M.text.charAt(v);
                        if(d.charAt(i)!=u){
                            ++i;
                            ++v;
                            break
                        }
                        u=="\n"&&G--;
                        if(v<=m||i<=m)break;
                        --i;
                        --v
                    }
                    A=M.text.lastIndexOf("\n",v-1);
                    z({
                        line:w,
                        ch:r
                    },{
                        line:G,
                        ch:A==-1?v:v-A-1
                    },va(d.slice(m,i)),l,o);
                    if(w!=G||l.line!=w)W=true
                }else xa(l,o);
                M.text=d;
                M.start=g.start;
                M.end=g.end;
                return c?"changed":j?"moved":false
            }
        }
        function Da(){
            var a=[],c=Math.max(0,n.from.line-1),d=Math.min(t.size,n.to.line+
                2);
            t.iter(c,d,function(j){
                a.push(j.text)
            });
            a=C.value=a.join(cb);
            var g=n.from.ch,i=n.to.ch;
            t.iter(c,n.from.line,function(j){
                g+=cb.length+j.text.length
            });
            t.iter(c,n.to.line,function(j){
                i+=cb.length+j.text.length
            });
            M={
                text:a,
                from:c,
                to:d,
                start:g,
                end:i
            };
    
            Pa(C,g,za?g:i)
        }
        function Aa(){
            s.readOnly!="nocursor"&&C.focus()
        }
        function Bc(){
            if(ia.getBoundingClientRect){
                var a=ia.getBoundingClientRect(),c=window.innerHeight||Math.max(document.body.offsetHeight,document.documentElement.offsetHeight);
                if(a.top<0||a.bottom>
                    c)ia.scrollIntoView()
            }
        }
        function $b(){
            var a=db(n.inverted?n.from:n.to),c=s.lineWrapping?Math.min(a.x,D.offsetWidth):a.x;
            return ac(c,a.y,c,a.yBot)
        }
        function ac(a,c,d,g){
            var i=D.offsetLeft,j=D.offsetTop,l=fa();
            c+=j;
            g+=j;
            a+=i;
            d+=i;
            var o=x.clientHeight,m=x.scrollTop;
            i=false;
            j=true;
            if(c<m){
                x.scrollTop=Math.max(0,c-2*l);
                i=true
            }else if(g>m+o){
                x.scrollTop=g+l-o;
                i=true
            }
            c=x.clientWidth;
            g=x.scrollLeft;
            l=s.fixedGutter?U.clientWidth:0;
            if(a<g+l){
                if(a<50)a=0;
                x.scrollLeft=Math.max(0,a-10-l);
                i=true
            }else if(d>c+g){
                x.scrollLeft=
                d+10-c;
                i=true;
                if(d>V.clientWidth)j=false
            }
            i&&s.onScroll&&s.onScroll(P);
            return j
        }
        function bc(){
            var a=fa(),c=x.scrollTop-D.offsetTop,d=Math.ceil((c+x.clientHeight)/a);
            return{
                from:eb(t,Math.max(0,Math.floor(c/a))),
                to:eb(t,d)
            }
        }
        function fb(a){
            if(x.clientWidth){
                var c=bc();
                if(!(a!==true&&a.length==0&&c.from>=N&&c.to<=Y)){
                    var d=Math.max(c.from-100,0);
                    c=Math.min(t.size,c.to+100);
                    if(N<d&&d-N<20)d=N;
                    if(Y>c&&Y-c<20)c=Math.min(t.size,Y);
                    a=a===true?[]:Cc([{
                        from:N,
                        to:Y,
                        domStart:0
                    }],a);
                    for(var g=0,i=0;i<a.length;++i){
                        var j=
                        a[i];
                        if(j.from<d){
                            j.domStart+=d-j.from;
                            j.from=d
                        }
                        if(j.to>c)j.to=c;
                        if(j.from>=j.to)a.splice(i--,1);else g+=j.to-j.from
                    }
                    if(g!=c-d){
                        a.sort(function(m,r){
                            return m.domStart-r.domStart
                        });
                        var l=fa();
                        g=U.style.display;
                        ba.style.display=U.style.display="none";
                        Dc(d,c,a);
                        ba.style.display="";
                        if(i=d!=N||c!=Y||cc!=x.clientHeight)cc=x.clientHeight;
                        N=d;
                        Y=c;
                        Ga=gb(t,d);
                        hb.style.top=Ga*l+"px";
                        V.style.height=t.height*l+2*D.offsetTop+"px";
                        if(ba.childNodes.length!=Y-N)throw Error("BAD PATCH! "+JSON.stringify(a)+" size="+
                            (Y-N)+" nodes="+ba.childNodes.length);
                        if(s.lineWrapping){
                            ra=x.clientWidth;
                            var o=ba.firstChild;
                            t.iter(N,Y,function(m){
                                if(!m.hidden){
                                    var r=Math.round(o.offsetHeight/l)||1;
                                    if(m.height!=r){
                                        k(m,r);
                                        ja=true
                                    }
                                }
                                o=o.nextSibling
                            })
                        }else{
                            if(ra==null)ra=Kb(qa);
                            if(ra>x.clientWidth){
                                D.style.width=ra+"px";
                                V.style.width="";
                                V.style.width=x.scrollWidth+"px"
                            }else D.style.width=V.style.width=""
                        }
                        U.style.display=g;
                        if(i||ja)dc();
                        ec()
                    }
                }
            }else N=Y=Ga=0
        }
        function Cc(a,c){
            for(var d=0,g=c.length||0;d<g;++d){
                for(var i=c[d],j=[],l=
                    i.diff||0,o=0,m=a.length;o<m;++o){
                    var r=a[o];
                    if(i.to<=r.from&&i.diff)j.push({
                        from:r.from+l,
                        to:r.to+l,
                        domStart:r.domStart
                    });
                    else if(i.to<=r.from||i.from>=r.to)j.push(r);
                    else{
                        i.from>r.from&&j.push({
                            from:r.from,
                            to:i.from,
                            domStart:r.domStart
                        });
                        i.to<r.to&&j.push({
                            from:i.to+l,
                            to:r.to+l,
                            domStart:r.domStart+(i.to-r.from)
                        })
                    }
                }
                a=j
            }
            return a
        }
        function Dc(a,c,d){
            if(d.length){
                for(var g=function(v){
                    var S=v.nextSibling;
                    v.parentNode.removeChild(v);
                    return S
                },i=0,j=ba.firstChild,l=0;l<d.length;++l){
                    for(var o=d[l];o.domStart>
                        i;){
                        j=g(j);
                        i++
                    }
                    var m=0;
                    for(o=o.to-o.from;m<o;++m){
                        j=j.nextSibling;
                        i++
                    }
                }
                for(;j;)j=g(j)
            }else ba.innerHTML="";
            var r=d.shift();
            j=ba.firstChild;
            m=a;
            var u=n.from.line,w=n.to.line,A=u<a&&w>=a,G=Ba.createElement("div");
            t.iter(a,c,function(v){
                var S=null,oa=null;
                if(A){
                    S=0;
                    if(w==m){
                        A=false;
                        oa=n.to.ch
                    }
                }else if(u==m)if(w==m){
                    S=n.from.ch;
                    oa=n.to.ch
                }else{
                    A=true;
                    S=n.from.ch
                }
                if(r&&r.to==m)r=d.shift();
                if(!r||r.from>m){
                    G.innerHTML=v.hidden?"<pre></pre>":v.getHTML(S,oa,true);
                    ba.insertBefore(G.firstChild,j)
                }else j=j.nextSibling;
                ++m
            })
        }
        function dc(){
            if(s.gutter||s.lineNumbers){
                var a=hb.offsetHeight,c=x.clientHeight;
                U.style.height=(a-c<2?c:a)+"px";
                var d=[],g=N;
                t.iter(N,Math.max(Y,N+1),function(l){
                    if(l.hidden)d.push("<pre></pre>");
                    else{
                        var o=l.gutterMarker,m=s.lineNumbers?g+s.firstLineNumber:null;
                        if(o&&o.text)m=o.text.replace("%N%",m!=null?m:"");
                        else if(m==null)m="\u00a0";
                        d.push(o&&o.style?'<pre class="'+o.style+'">':"<pre>",m);
                        for(o=1;o<l.height;++o)d.push("<br>&nbsp;");
                        d.push("</pre>")
                    }
                    ++g
                });
                U.style.display="none";
                Qa.innerHTML=
                d.join("");
                a=String(t.size).length;
                c=Qa.firstChild;
                for(var i=c.textContent||c.innerText||c.nodeValue||"",j="";i.length+j.length<a;)j+="\u00a0";
                j&&c.insertBefore(Ba.createTextNode(j),c.firstChild);
                U.style.display="";
                D.style.marginLeft=U.offsetWidth+"px";
                ja=false
            }
        }
        function ec(){
            var a=n.inverted?n.from:n.to;
            fa();
            a=db(a,true);
            var c=a.y+Ga*fa();
            Ra.style.top=Math.max(Math.min(c,x.offsetHeight),0)+"px";
            Ra.style.left=a.x-x.scrollLeft+"px";
            if(J(n.from,n.to)){
                ia.style.top=a.y+"px";
                ia.style.left=(s.lineWrapping?
                    Math.min(a.x,D.offsetWidth):a.x)+"px";
                ia.style.display=""
            }else ia.style.display="none"
        }
        function sa(a,c){
            var d=ca&&I(ca);
            if(d)if(ga(d,a))a=d;
                else if(ga(c,d))c=d;
            xa(a,c)
        }
        function xa(a,c,d,g){
            if(d==null){
                d=n.from.line;
                g=n.to.line
            }
            if(!(J(n.from,a)&&J(n.to,c))){
                if(ga(c,a)){
                    var i=c;
                    c=a;
                    a=i
                }
                if(a.line!=d)a=ib(a,d,n.from.ch);
                if(c.line!=g)c=ib(c,g,n.to.ch);
                if(J(a,c))n.inverted=false;
                else if(J(a,n.to))n.inverted=false;
                else if(J(c,n.from))n.inverted=true;
                if(J(a,c))J(n.from,n.to)||L.push({
                    from:d,
                    to:g+1
                });
                else if(J(n.from,
                    n.to))L.push({
                    from:a.line,
                    to:c.line+1
                });
                else{
                    J(a,n.from)||(a.line<d?L.push({
                        from:a.line,
                        to:Math.min(c.line,d)+1
                    }):L.push({
                        from:d,
                        to:Math.min(g,a.line)+1
                    }));
                    J(c,n.to)||(c.line<g?L.push({
                        from:Math.max(d,a.line),
                        to:g+1
                    }):L.push({
                        from:Math.max(a.line,g),
                        to:c.line+1
                    }))
                }
                n.from=a;
                n.to=c;
                ta=true
            }
        }
        function ib(a,c,d){
            function g(i){
                for(var j=a.line+i,l=i==1?t.size:-1;j!=l;){
                    var o=f(j);
                    if(!o.hidden){
                        i=a.ch;
                        if(i>d||i>o.text.length)i=o.text.length;
                        return{
                            line:j,
                            ch:i
                        }
                    }
                    j+=i
                }
            }
            if(!f(a.line).hidden)return a;
            return a.line>=
            c?g(1)||g(-1):g(-1)||g(1)
        }
        function ua(a,c,d){
            a=I({
                line:a,
                ch:c||0
            });
            (d?sa:xa)(a,a)
        }
        function jb(a){
            return Math.max(0,Math.min(a,t.size-1))
        }
        function I(a){
            if(a.line<0)return{
                line:0,
                ch:0
            };
    
            if(a.line>=t.size)return{
                line:t.size-1,
                ch:f(t.size-1).text.length
            };
        
            var c=a.ch,d=f(a.line).text.length;
            return c==null||c>d?{
                line:a.line,
                ch:d
            }:c<0?{
                line:a.line,
                ch:0
            }:a
        }
        function fc(a){
            for(var c=f(a.line).text,d=a.ch,g=a.ch;d>0&&/\w/.test(c.charAt(d-1));)--d;
            for(;g<c.length&&/\w/.test(c.charAt(g));)++g;
            sa({
                line:a.line,
                ch:d
            },

            {
                line:a.line,
                ch:g
            })
        }
        function Ec(a){
            sa({
                line:a,
                ch:0
            },{
                line:a,
                ch:f(a).text.length
            })
        }
        function Fc(a){
            function c(d){
                if(J(n.from,n.to))return Ha(n.from.line,d);
                for(var g=n.to.line-(n.to.ch?0:1),i=n.from.line;i<=g;++i)Ha(i,d)
            }
            ca=null;
            switch(s.tabMode){
                case "default":
                    return false;
                case "indent":
                    c("smart");
                    break;
                case "classic":
                    if(J(n.from,n.to)){
                        a?Ha(n.from.line,"smart"):Q("\t","end");
                        break
                    }
                case "shift":
                    c(a?"subtract":"add")
            }
            return true
        }
        function Ha(a,c){
            if(c=="smart")if(O.indent)var d=kb(a);else c="prev";
            var g=
            f(a),i=g.indentation(),j=g.text.match(/^\s*/)[0],l;
            if(c=="prev")l=a?f(a-1).indentation():0;
            else if(c=="smart")l=O.indent(d,g.text.slice(j.length));
            else if(c=="add")l=i+s.indentUnit;
            else if(c=="subtract")l=i-s.indentUnit;
            l=Math.max(0,l);
            if(l-i){
                i="";
                d=0;
                if(s.indentWithTabs)for(g=Math.floor(l/lb);g;--g){
                    d+=lb;
                    i+="\t"
                }
                for(;d<l;){
                    ++d;
                    i+=" "
                }
            }else{
                if(n.from.line!=a&&n.to.line!=a)return;
                i=j
            }
            X(i,{
                line:a,
                ch:0
            },{
                line:a,
                ch:j.length
            })
        }
        function gc(){
            O=F.getMode(s,s.mode);
            t.iter(0,t.size,function(a){
                a.stateAfter=
                null
            });
            ea=[0];
            Eb()
        }
        function Gc(){
            var a=s.gutter||s.lineNumbers;
            U.style.display=a?"":"none";
            if(a)ja=true;else ba.parentNode.style.marginLeft=0
        }
        function Hc(){
            if(s.lineWrapping){
                K.className+=" CodeMirror-wrap";
                var a=x.clientWidth/Db()-3;
                t.iter(0,t.size,function(c){
                    if(!c.hidden){
                        var d=Math.ceil(c.text.length/a)||1;
                        d!=1&&k(c,d)
                    }
                });
                D.style.width=V.style.width=""
            }
            else{
                K.className=K.className.replace(" CodeMirror-wrap","");
                ra=null;
                qa="";
                t.iter(0,t.size,function(c){
                    c.height!=1&&!c.hidden&&k(c,1);
                    if(c.text.length>
                        qa.length)qa=c.text
                })
            }
            L.push({
                from:0,
                to:t.size
            })
        }
        function Lb(){
            this.set=[]
        }
        function Mb(a,c,d){
            function g(o,m,r,u){
                mark=f(o).addMark(new mb(m,r,u,i.set))
            }
            a=I(a);
            c=I(c);
            var i=new Lb;
            if(a.line==c.line)g(a.line,a.ch,c.ch,d);
            else{
                g(a.line,a.ch,null,d);
                for(var j=a.line+1,l=c.line;j<l;++j)g(j,null,null,d);
                g(c.line,null,c.ch,d)
            }
            L.push({
                from:a.line,
                to:c.line+1
            });
            return i
        }
        function hc(a,c){
            var d=a,g=a;
            if(typeof a=="number")g=f(jb(a));else d=nb(a);
            if(d==null)return null;
            c(g,d)&&L.push({
                from:d,
                to:d+1
            });
            return g
        }
        function ic(a,c){
            return hc(a,function(d,g){
                if(d.hidden!=c){
                    d.hidden=c;
                    k(d,c?0:1);
                    if(c&&(n.from.line==g||n.to.line==g))xa(ib(n.from,n.from.line,n.from.ch),ib(n.to,n.to.line,n.to.ch));
                    return ja=true
                }
            })
        }
        function Kb(a){
            Ca.innerHTML="<pre><span>x</span></pre>";
            Ca.firstChild.firstChild.firstChild.nodeValue=a;
            return Ca.firstChild.firstChild.offsetWidth||10
        }
        function jc(a,c){
            var d="";
            if(s.lineWrapping){
                d=a.text.indexOf(" ",c+2);
                d=a.text.slice(c+1,d<0?a.text.length:d+(ob?5:0))
            }
            Ca.innerHTML="<pre>"+a.getHTML(null,
                null,false,c)+'<span id="CodeMirror-temp-'+kc+'">'+(a.text.charAt(c)||" ")+"</span>"+d+"</pre>";
            d=document.getElementById("CodeMirror-temp-"+kc);
            var g=d.offsetTop,i=d.offsetLeft;
            if(ob&&c&&g==0&&i==0){
                g=document.createElement("span");
                g.innerHTML="x";
                d.parentNode.insertBefore(g,d.nextSibling);
                g=g.offsetTop
            }
            return{
                top:g,
                left:i
            }
        }
        function db(a,c){
            var d,g=fa(),i=g*(gb(t,a.line)-(c?Ga:0));
            if(a.ch==0)d=0;
            else{
                var j=jc(f(a.line),a.ch);
                d=j.left;
                if(s.lineWrapping)i+=Math.max(0,j.top)
            }
            return{
                x:d,
                y:i,
                yBot:i+g
            }
        }
        function lc(a,c){
            function d(v){
                v=jc(o,v);
                if(r)return Math.max(0,v.left+(Math.round(v.top/g)-u)*x.clientWidth);
                return v.left
            }
            if(c<0)c=0;
            var g=fa(),i=Db(),j=Ga+Math.floor(c/g),l=eb(t,j);
            if(l>=t.size)return{
                line:t.size-1,
                ch:0
            };
    
            var o=f(l),m=o.text,r=s.lineWrapping,u=r?j-gb(t,l):0;
            if(a<=0&&u==0)return{
                line:l,
                ch:0
            };
    
            var w=j=0;
            m=m.length;
            var A;
            for(i=Math.min(m,Math.ceil((a+u*x.clientWidth*0.9)/i));;){
                var G=d(i);
                if(G<=a&&i<m)i=Math.min(m,Math.ceil(i*1.2));
                else{
                    A=G;
                    m=i;
                    break
                }
            }
            if(a>A)return{
                line:l,
                ch:m
            };

            i=Math.floor(m*
                0.8);
            G=d(i);
            if(G<a){
                j=i;
                w=G
            }
            for(;;){
                if(m-j<=1)return{
                    line:l,
                    ch:A-a>a-w?j:m
                };
        
                i=Math.ceil((j+m)/2);
                G=d(i);
                if(G>a){
                    m=i;
                    A=G
                }else{
                    j=i;
                    w=G
                }
            }
        }
        function mc(a){
            a=db(a,true);
            var c=Sa(D);
            return{
                x:c.left+a.x,
                y:c.top+a.y,
                yBot:c.top+a.yBot
            }
        }
        function fa(){
            var a=ba.offsetHeight;
            if(a==Ta)return nc;
            Ta=a;
            Ca.innerHTML="<pre>x<br>x<br>x<br>x<br>x<br>x<br>x<br>x<br>x<br>x</pre>";
            return nc=Ca.firstChild.offsetHeight/10||1
        }
        function Db(){
            if(x.clientWidth==Ta)return oc;
            Ta=x.clientWidth;
            return oc=Kb("x")
        }
        function Ia(a,c){
            var d=
            Sa(x,true),g,i;
            try{
                g=a.clientX;
                i=a.clientY
            }catch(j){
                return null
            }
            if(!c&&(g-d.left>x.clientWidth||i-d.top>x.clientHeight))return null;
            d=Sa(D,true);
            return lc(g-d.left,i-d.top)
        }
        function pc(a){
            function c(){
                var l=va(C.value).join("\n");
                l!=i&&y(Q)(l,"end");
                Ra.style.position="relative";
                C.style.cssText=g;
                Na=false;
                Da();
                pa()
            }
            var d=Ia(a);
            if(!(!d||window.opera)){
                if(J(n.from,n.to)||ga(d,n.from)||!ga(d,n.to))y(ua)(d.line,d.ch);
                var g=C.style.cssText;
                Ra.style.position="absolute";
                C.style.cssText="position: fixed; width: 30px; height: 30px; top: "+
                (a.clientY-5)+"px; left: "+(a.clientX-5)+"px; z-index: 1000; background: white; border-width: 0; outline: none; overflow: hidden; opacity: .05; filter: alpha(opacity=5);";
                Na=true;
                var i=C.value=ma();
                Aa();
                Pa(C,0,C.value.length);
                if(Ja){
                    pb(a);
                    var j=E(window,"mouseup",function(){
                        j();
                        setTimeout(c,20)
                    },true)
                }else setTimeout(c,50)
            }
        }
        function Zb(){
            clearInterval(Cb);
            var a=true;
            ia.style.visibility="";
            Cb=setInterval(function(){
                ia.style.visibility=(a=!a)?"":"hidden"
            },650)
        }
        function qc(a){
            function c(T,ya,Ic){
                if(T.text){
                    var Ua=
                    T.styles;
                    T=l?0:T.text.length-1;
                    for(var Nb,Va=l?0:Ua.length-2,Jc=l?Ua.length:-2;Va!=Jc;Va+=2*o){
                        var qb=Ua[Va];
                        if(Ua[Va+1]!=null&&Ua[Va+1]!=w)T+=o*qb.length;else for(var Ob=l?0:qb.length-1,Kc=l?qb.length:-1;Ob!=Kc;Ob+=o,T+=o)if(T>=ya&&T<Ic&&G.test(Nb=qb.charAt(Ob))){
                            var rc=Pb[Nb];
                            if(rc.charAt(1)==">"==l)A.push(Nb);
                            else if(A.pop()!=rc.charAt(0))return{
                                pos:T,
                                match:false
                            };
                            else if(!A.length)return{
                                pos:T,
                                match:true
                            }
                        }
                    }
                }
            }
            var d=n.inverted?n.from:n.to,g=f(d.line),i=d.ch-1,j=i>=0&&Pb[g.text.charAt(i)]||Pb[g.text.charAt(++i)];
            if(j){
                j.charAt(0);
                var l=j.charAt(1)==">",o=l?1:-1,m=g.styles,r=i+1;
                j=0;
                for(var u=m.length;j<u;j+=2)if((r-=m[j].length)<=0){
                    var w=m[j+1];
                    break
                }
                var A=[g.text.charAt(i)],G=/[(){}[\]]/;
                j=d.line;
                for(u=l?Math.min(j+100,t.size):Math.max(-1,j-100);j!=u;j+=o){
                    g=f(j);
                    var v=j==d.line;
                    if(v=c(g,v&&l?i+1:0,v&&!l?i:g.text.length))break
                }
                v||(v={
                    pos:null,
                    match:false
                });
                w=v.match?"CodeMirror-matchingbracket":"CodeMirror-nonmatchingbracket";
                var S=Mb({
                    line:d.line,
                    ch:i
                },{
                    line:d.line,
                    ch:i+1
                },w),oa=v.pos!=null&&Mb({
                    line:j,
                    ch:v.pos
                },{
                    line:j,
                    ch:v.pos+1
                },w);
                d=y(function(){
                    S.clear();
                    oa&&oa.clear()
                });
                if(a)setTimeout(d,800);else rb=d
            }
        }
        function sc(a){
            var c,d,g=a;
            for(a=a-40;g>a;--g){
                if(g==0)return 0;
                var i=f(g-1);
                if(i.stateAfter)return g;
                i=i.indentation();
                if(d==null||c>i){
                    d=g-1;
                    c=i
                }
            }
            return d
        }
        function kb(a){
            var c=sc(a),d=c&&f(c-1).stateAfter;
            d=d?Ka(O,d):Qb(O);
            t.iter(c,a,function(g){
                g.highlight(O,d);
                g.stateAfter=Ka(O,d)
            });
            c<a&&L.push({
                from:c,
                to:a
            });
            a<t.size&&!f(a).stateAfter&&ea.push(a);
            return d
        }
        function Ac(a,c){
            var d=kb(a);
            t.iter(a,c,function(g){
                g.highlight(O,d);
                g.stateAfter=Ka(O,d)
            })
        }
        function Lc(){
            for(var a=+new Date+s.workTime,c=ea.length;ea.length;){
                var d=f(N).stateAfter?ea.pop():N;
                if(!(d>=t.size)){
                    var g=sc(d),i=g&&f(g-1).stateAfter;
                    i=i?Ka(O,i):Qb(O);
                    var j=0,l=O.compareStates,o=false,m=g,r=false;
                    t.iter(m,t.size,function(u){
                        var w=u.stateAfter;
                        if(+new Date>a){
                            ea.push(m);
                            Eb(s.workDelay);
                            o&&L.push({
                                from:d,
                                to:m+1
                            });
                            return r=true
                        }
                        var A=u.highlight(O,i);
                        if(A)o=true;
                        u.stateAfter=Ka(O,i);
                        if(l){
                            if(w&&l(w,i))return true
                        }else if(A!==
                            false||!w)j=0;
                        else if(++j>3)return true;
                        ++m
                    });
                    if(r)return;
                    o&&L.push({
                        from:d,
                        to:m+1
                    })
                }
            }
            c&&s.onHighlightComplete&&s.onHighlightComplete(P)
        }
        function Eb(a){
            ea.length&&Mc.set(a,y(Lc))
        }
        function Ib(){
            W=null;
            L=[];
            Fb=ta=false
        }
        function Jb(){
            var a=false;
            if(ta)a=!$b();
            if(L.length)fb(L);
            else{
                ta&&ec();
                ja&&dc()
            }
            a&&$b();
            if(ta){
                Bc();
                Zb()
            }
            if(Z&&!Na&&(W===true||W!==false&&ta))Da();
            ta&&s.matchBrackets&&setTimeout(y(function(){
                if(rb){
                    rb();
                    rb=null
                }
                qc(false)
            }),20);
            a=Fb;
            ta&&s.onCursorActivity&&s.onCursorActivity(P);
            a&&s.onChange&&
            P&&s.onChange(P,a)
        }
        function y(a){
            return function(){
                tc++||Ib();
                try{
                    var c=a.apply(this,arguments)
                }finally{
                    --tc||Jb()
                }
                return c
            }
        }
        function uc(a,c,d){
            this.atOccurrence=false;
            if(d==null)d=typeof a=="string"&&a==a.toLowerCase();
            c=c&&typeof c=="object"?I(c):{
                line:0,
                ch:0
            };
    
            this.pos={
                from:c,
                to:c
            };
    
            if(typeof a!="string")this.matches=function(j,l){
                if(j)for(var o=f(l.line).text.slice(0,l.ch),m=o.match(a),r=0;m;){
                    var u=o.indexOf(m[0]);
                    r+=u;
                    o=o.slice(u+1);
                    if(u=o.match(a))m=u;else break;
                    r++
                }else{
                    o=f(l.line).text.slice(l.ch);
                    r=(m=o.match(a))&&l.ch+o.indexOf(m[0])
                }
                if(m)return{
                    from:{
                        line:l.line,
                        ch:r
                    },
                    to:{
                        line:l.line,
                        ch:r+m[0].length
                    },
                    match:m
                }
            };
            else{
                if(d)a=a.toLowerCase();
                var g=d?function(j){
                    return j.toLowerCase()
                }:function(j){
                    return j
                },i=a.split("\n");
                this.matches=i.length==1?function(j,l){
                    var o=g(f(l.line).text),m=a.length,r;
                    if(j?l.ch>=m&&(r=o.lastIndexOf(a,l.ch-m))!=-1:(r=o.indexOf(a,l.ch))!=-1)return{
                        from:{
                            line:l.line,
                            ch:r
                        },
                        to:{
                            line:l.line,
                            ch:r+m
                        }
                    }
                }:function(j,l){
                    var o=l.line,m=j?i.length-1:0,r=i[m],u=g(f(o).text),w=
                    j?u.indexOf(r)+r.length:u.lastIndexOf(r);
                    if(!(j?w>=l.ch||w!=r.length:w<=l.ch||w!=u.length-r.length))for(;;){
                        if(j?!o:o==t.size-1)break;
                        u=g(f(o+=j?-1:1).text);
                        r=i[j?--m:++m];
                        if(m>0&&m<i.length-1)if(u!=r)break;else continue;
                        m=j?u.lastIndexOf(r):u.indexOf(r)+r.length;
                        if(j?m!=u.length-r.length:m!=r.length)break;
                        r={
                            line:l.line,
                            ch:w
                        };
        
                        o={
                            line:o,
                            ch:m
                        };
        
                        return{
                            from:j?o:r,
                            to:j?r:o
                        }
                    }
                }
            }
        }
        var s={},Rb=F.defaults,Wa;
        for(Wa in Rb)if(Rb.hasOwnProperty(Wa))s[Wa]=(e&&e.hasOwnProperty(Wa)?e:Rb)[Wa];var Ba=s.document,K=
        Ba.createElement("div");
        K.className="CodeMirror"+(s.lineWrapping?" CodeMirror-wrap":"");
        K.innerHTML='<div style="overflow: hidden; position: relative; width: 1px; height: 0px;"><textarea style="position: absolute; width: 10000px;" wrap="off" autocorrect="off" autocapitalize="off"></textarea></div><div class="CodeMirror-scroll cm-s-'+s.theme+'"><div style="position: relative"><div style="position: relative"><div class="CodeMirror-gutter"><div class="CodeMirror-gutter-text"></div></div><div class="CodeMirror-lines"><div style="position: relative"><div style="position: absolute; width: 100%; height: 0; overflow: hidden; visibility: hidden"></div><pre class="CodeMirror-cursor">&#160;</pre><div></div></div></div></div></div></div>';
        b.appendChild?b.appendChild(K):b(K);
        var Ra=K.firstChild,C=Ra.firstChild,x=K.lastChild,V=x.firstChild,hb=V.firstChild,U=hb.firstChild,Qa=U.firstChild,D=U.nextSibling.firstChild,Ca=D.firstChild,ia=Ca.nextSibling,ba=ia.nextSibling;
        if(!sb)D.draggable=true;
        if(s.tabindex!=null)C.tabindex=s.tabindex;
        if(!s.gutter&&!s.lineNumbers)U.style.display="none";
        try{
            Kb("x")
        }catch(Sb){
            if(Sb.message.match(/unknown runtime/i))Sb=Error("A CodeMirror inside a P-style element does not work in Internet Explorer. (innerHTML bug)");
            throw Sb;
        }
        var Hb=new Tb,Mc=new Tb,Cb,O,t=new tb([new ub([new wa("")])]),ea,Z;
        gc();
        var n={
            from:{
                line:0,
                ch:0
            },
            to:{
                line:0,
                ch:0
            },
            inverted:false
        },ca,za,vb,Xa,Ub,W,L,Fb,ta,Na,ja,Ga=0,N=0,Y=0,cc=0,La=null,M,rb,qa="",ra;
        y(function(){
            p(s.value||"");
            W=false
        })();
        var $=new Vb,Ea=2E3,Wb=!vc&&!Ya&&(Ja||window.opera);
        if(s.pollForIME&&Wb)Ea=50;
        E(x,"mousedown",y(function(a){
            function c(m){
                var r=Ia(m,true);
                if(r&&!J(r,i)){
                    Z||q();
                    i=r;
                    sa(g,r);
                    W=false;
                    var u=bc();
                    if(r.line>=u.to||r.line<u.from)j=setTimeout(y(function(){
                        c(m)
                    }),
                    150)
                }
            }
            for(var d=a.target||a.srcElement;d!=K;d=d.parentNode)if(d.parentNode==V&&d!=hb)return;for(d=a.target||a.srcElement;d!=K;d=d.parentNode)if(d.parentNode==Qa){
                s.onGutterClick&&s.onGutterClick(P,Za(Qa.childNodes,d)+N,a);
                return H(a)
            }
            var g=Ia(a);
            switch(Nc(a)){
                case 3:
                    Ja&&!Ya&&pc(a);
                    return;
                case 2:
                    g&&ua(g.line,g.ch,true);
                    return
            }
            if(g){
                Z||q();
                d=+new Date;
                if(Xa&&Xa.time>d-400&&J(Xa.pos,g)){
                    H(a);
                    setTimeout(Aa,20);
                    return Ec(g.line)
                }else if(vb&&vb.time>d-400&&J(vb.pos,g)){
                    Xa={
                        time:d,
                        pos:g
                    };
        
                    H(a);
                    return fc(g)
                }else vb=

                {
                        time:d,
                        pos:g
                    };
    
                var i=g,j;
                if(wc&&!J(n.from,n.to)&&!ga(g,n.from)&&!ga(n.to,g)){
                    if(sb)D.draggable=true;
                    var l=E(Ba,"mouseup",y(function(m){
                        if(sb)D.draggable=false;
                        Ub=false;
                        l();
                        if(Math.abs(a.clientX-m.clientX)+Math.abs(a.clientY-m.clientY)<10){
                            H(m);
                            ua(g.line,g.ch,true);
                            Aa()
                        }
                    }),true);
                    Ub=true
                }else{
                    H(a);
                    ua(g.line,g.ch,true);
                    var o=E(Ba,"mousemove",y(function(m){
                        clearTimeout(j);
                        H(m);
                        c(m)
                    }),true);
                    l=E(Ba,"mouseup",y(function(m){
                        clearTimeout(j);
                        var r=Ia(m);
                        r&&sa(g,r);
                        H(m);
                        Aa();
                        W=true;
                        o();
                        l()
                    }),true)
                }
            }else(a.target||
                a.srcElement)==x&&H(a)
        }));
        E(x,"dblclick",y(function(a){
            for(var c=a.target||a.srcElement;c!=K;c=c.parentNode)if(c.parentNode==Qa)return H(a);if(c=Ia(a)){
                Xa={
                    time:+new Date,
                    pos:c
                };
        
                H(a);
                fc(c)
            }
        }));
        E(D,"dragstart",function(a){
            var c=ma();
            $a(c);
            a.dataTransfer.setDragImage(ab,0,0);
            a.dataTransfer.setData("Text",c)
        });
        E(D,"selectstart",H);
        Ja||E(x,"contextmenu",pc);
        E(x,"scroll",function(){
            fb([]);
            if(s.fixedGutter)U.style.left=x.scrollLeft+"px";
            s.onScroll&&s.onScroll(P)
        });
        E(window,"resize",function(){
            fb(true)
        });
        E(C,"keyup",y(function(a){
            if(!(s.onKeyEvent&&s.onKeyEvent(P,Xb(a)))){
                if(za){
                    za=null;
                    W=true
                }
                if(a.keyCode==16)ca=null;
                if(Ea<2E3&&!Wb)Ea=2E3
            }
        }));
        E(C,"input",function(){
            ha(La)
        });
        E(C,"keydown",y(function(a){
            Z||q();
            var c=a.keyCode;
            if(ob&&c==27)a.returnValue=false;
            var d=(Ya?a.metaKey:a.ctrlKey)&&!a.altKey,g=a.ctrlKey||a.altKey||a.metaKey;
            ca=c==16||a.shiftKey?ca||(n.inverted?n.to:n.from):null;
            if(!(s.onKeyEvent&&s.onKeyEvent(P,Xb(a)))){
                if(c==33||c==34){
                    d=c==34;
                    g=Math.floor(x.clientHeight/fa());
                    c=n.inverted?
                    n.from:n.to;
                    d=gb(t,c.line)+Math.max(g-1,1)*(d?1:-1);
                    ua(eb(t,d),c.ch,true);
                    return H(a)
                }
                if(d&&(c==36||c==35||Ya&&(c==38||c==40))){
                    c=c==36||c==38?{
                        line:0,
                        ch:0
                    }:{
                        line:t.size-1,
                        ch:f(t.size-1).text.length
                    };
                
                    sa(c,c);
                    return H(a)
                }
                if(d&&c==65){
                    c=t.size-1;
                    xa({
                        line:0,
                        ch:0
                    },{
                        line:c,
                        ch:f(c).text.length
                    });
                    return H(a)
                }
                if(!s.readOnly){
                    if(!g&&c==13)return;
                    if(!g&&c==9&&Fc(a.shiftKey))return H(a);
                    if(d&&c==90){
                        da();
                        return H(a)
                    }
                    if(d&&(a.shiftKey&&c==90||c==89)){
                        la();
                        return H(a)
                    }
                }
                if(c==36)if(s.smartHome){
                    c=Math.max(0,f(n.from.line).text.search(/\S/));
                    ua(n.from.line,n.from.ch<=c&&n.from.ch?0:c,true);
                    return H(a)
                }
                La=(d?"c":"")+(a.altKey?"a":"")+c;
                if(n.inverted&&Fa[La]===true)if(g=bb(C)){
                    za={
                        anchor:g.start
                    };
            
                    Pa(C,g.start,g.start)
                }
                if(!d&&!a.altKey)La=null;
                ha(La);
                if(s.pollForIME&&(vc&&(Ja&&c==229||window.opera&&c==197)||Ya&&Ja))Ea=50
            }
        }));
        E(C,"keypress",y(function(a){
            if(!(s.onKeyEvent&&s.onKeyEvent(P,Xb(a)))){
                if(s.electricChars&&O.electricChars){
                    var c=String.fromCharCode(a.charCode==null?a.keyCode:a.charCode);
                    O.electricChars.indexOf(c)>-1&&setTimeout(y(function(){
                        Ha(n.to.line,
                            "smart")
                    }),50)
                }
                c=a.keyCode;
                if(c==13){
                    if(!s.readOnly){
                        Q("\n","end");
                        if(s.enterMode!="flat")Ha(n.from.line,s.enterMode=="keep"?"prev":"smart")
                    }
                    H(a)
                }else!a.ctrlKey&&!a.altKey&&!a.metaKey&&c==9&&s.tabMode!="default"?H(a):ha(La)
            }
        }));
        E(C,"focus",q);
        E(C,"blur",B);
        E(x,"dragenter",pb);
        E(x,"dragover",pb);
        E(x,"drop",y(function(a){
            a.preventDefault();
            var c=Ia(a,true),d=a.dataTransfer.files;
            if(!(!c||s.readOnly))if(d&&d.length&&window.FileReader&&window.File){
                a=function(u,w){
                    var A=new FileReader;
                    A.onload=function(){
                        i[w]=
                        A.result;
                        if(++j==g){
                            c=I(c);
                            y(function(){
                                var G=X(i.join(""),c,c);
                                sa(c,G)
                            })()
                        }
                    };
            
                    A.readAsText(u)
                };
        
                for(var g=d.length,i=Array(g),j=0,l=0;l<g;++l)a(d[l],l)
            }else try{
                if(i=a.dataTransfer.getData("Text")){
                    l=X(i,c,c);
                    var o=n.from,m=n.to;
                    sa(c,l);
                    Ub&&X("",o,m);
                    Aa()
                }
            }catch(r){}
        }));
        E(x,"paste",function(){
            Aa();
            ha()
        });
        E(C,"paste",function(){
            ha()
        });
        E(C,"cut",function(){
            ha()
        });
        var xc;
        try{
            xc=Ba.activeElement==C
        }catch(Qc){}
        xc?setTimeout(q,20):B();
        var P=K.CodeMirror={
            getValue:function(){
                var a=[];
                t.iter(0,t.size,function(c){
                    a.push(c.text)
                });
                return a.join("\n")
            },
            setValue:y(p),
            getSelection:ma,
            replaceSelection:y(Q),
            focus:function(){
                Aa();
                q();
                ha()
            },
            setOption:function(a,c){
                var d=s[a];
                s[a]=c;
                if(a=="mode"||a=="indentUnit")gc();
                else if(a=="readOnly"&&c=="nocursor")C.blur();
                else if(a=="theme")x.className=x.className.replace(/cm-s-\w+/,"cm-s-"+c);
                else if(a=="lineWrapping"&&d!=c)y(Hc)();
                else if(a=="pollForIME"&&Wb)Ea=c?50:2E3;
                if(a=="lineNumbers"||a=="gutter"||a=="firstLineNumber"||a=="theme")y(Gc)()
            },
            getOption:function(a){
                return s[a]
            },
            undo:y(da),
            redo:y(la),
            indentLine:y(function(a,c){
                if(h(a))Ha(a,c==null?"smart":c?"add":"subtract")
            }),
            historySize:function(){
                return{
                    undo:$.done.length,
                    redo:$.undone.length
                }
            },
            clearHistory:function(){
                $=new Vb
            },
            matchBrackets:y(function(){
                qc(true)
            }),
            getTokenAt:y(function(a){
                a=I(a);
                return f(a.line).getTokenAt(O,kb(a.line),a.ch)
            }),
            getStateAfter:function(a){
                a=jb(a==null?t.size-1:a);
                return kb(a+1)
            },
            cursorCoords:function(a){
                if(a==null)a=n.inverted;
                return mc(a?n.from:n.to)
            },
            charCoords:function(a){
                return mc(I(a))
            },
            coordsChar:function(a){
                var c=
                Sa(D);
                return lc(a.x-c.left,a.y-c.top)
            },
            getSearchCursor:function(a,c,d){
                return new uc(a,c,d)
            },
            markText:y(Mb),
            setBookmark:function(a){
                a=I(a);
                var c=new yc(a.ch);
                f(a.line).addMark(c);
                return c
            },
            setMarker:y(function(a,c,d){
                if(typeof a=="number")a=f(jb(a));
                a.gutterMarker={
                    text:c,
                    style:d
                };
    
                ja=true;
                return a
            }),
            clearMarker:y(function(a){
                if(typeof a=="number")a=f(jb(a));
                a.gutterMarker=null;
                ja=true
            }),
            setLineClass:y(function(a,c){
                return hc(a,function(d){
                    if(d.className!=c){
                        d.className=c;
                        return true
                    }
                })
            }),
            hideLine:y(function(a){
                return ic(a,
                    true)
            }),
            showLine:y(function(a){
                return ic(a,false)
            }),
            lineInfo:function(a){
                if(typeof a=="number"){
                    if(!h(a))return null;
                    var c=a;
                    a=f(a);
                    if(!a)return null
                }else{
                    c=nb(a);
                    if(c==null)return null
                }
                var d=a.gutterMarker;
                return{
                    line:c,
                    handle:a,
                    text:a.text,
                    markerText:d&&d.text,
                    markerClass:d&&d.style,
                    lineClass:a.className
                }
            },
            addWidget:function(a,c,d,g,i){
                a=db(I(a));
                var j=a.yBot,l=a.x;
                c.style.position="absolute";
                V.appendChild(c);
                if(g=="over")j=a.y;
                else if(g=="near"){
                    g=Math.max(x.offsetHeight,t.height*fa());
                    var o=Math.max(V.clientWidth,
                        D.clientWidth)-D.offsetLeft;
                    if(a.yBot+c.offsetHeight>g&&a.y>c.offsetHeight)j=a.y-c.offsetHeight;
                    if(l+c.offsetWidth>o)l=o-c.offsetWidth
                }
                c.style.top=j+D.offsetTop+"px";
                c.style.left=c.style.right="";
                if(i=="right"){
                    l=V.clientWidth-c.offsetWidth;
                    c.style.right="0px"
                }else{
                    if(i=="left")l=0;
                    else if(i=="middle")l=(V.clientWidth-c.offsetWidth)/2;
                    c.style.left=l+D.offsetLeft+"px"
                }
                d&&ac(l,j,l+c.offsetWidth,j+c.offsetHeight)
            },
            lineCount:function(){
                return t.size
            },
            getCursor:function(a){
                if(a==null)a=n.inverted;
                return{
                    line:(a?
                        n.from:n.to).line,
                    ch:(a?n.from:n.to).ch
                }
            },
            somethingSelected:function(){
                return!J(n.from,n.to)
            },
            setCursor:y(function(a,c){
                c==null&&typeof a.line=="number"?ua(a.line,a.ch):ua(a,c)
            }),
            setSelection:y(function(a,c){
                xa(I(a),I(c||a))
            }),
            getLine:function(a){
                if(h(a))return f(a).text
            },
            setLine:y(function(a,c){
                h(a)&&X(c,{
                    line:a,
                    ch:0
                },{
                    line:a,
                    ch:f(a).text.length
                })
            }),
            removeLine:y(function(a){
                h(a)&&X("",{
                    line:a,
                    ch:0
                },I({
                    line:a+1,
                    ch:0
                }))
            }),
            replaceRange:y(X),
            getRange:function(a,c){
                return R(I(a),I(c))
            },
            coordsFromIndex:function(a){
                var c=
                0,d;
                t.iter(0,t.size,function(g){
                    g=g.text.length+1;
                    if(g>a){
                        d=a;
                        return true
                    }
                    a-=g;
                    ++c
                });
                return I({
                    line:c,
                    ch:d
                })
            },
            operation:function(a){
                return y(a)()
            },
            refresh:function(){
                fb(true)
            },
            getInputField:function(){
                return C
            },
            getWrapperElement:function(){
                return K
            },
            getScrollerElement:function(){
                return x
            },
            getGutterElement:function(){
                return U
            }
        },Gb=false;
        Lb.prototype.clear=y(function(){
            for(var a=0,c=this.set.length;a<c;++a){
                var d=this.set[a].marked;
                if(d)for(var g=0;g<d.length;++g)d[g].set==this.set&&d.splice(g--,1)
            }
            L.push({
                from:0,
                to:t.size
            })
        });
        Lb.prototype.find=function(){
            for(var a,c,d=0,g=this.set.length;d<g;++d)for(var i=this.set[d],j=i.marked,l=0;l<j.length;++l){
                var o=j[l];
                if(o.set==this.set)if(o.from!=null||o.to!=null){
                    var m=nb(i);
                    if(m!=null){
                        if(o.from!=null)a={
                            line:m,
                            ch:o.from
                        };
                    
                        if(o.to!=null)c={
                            line:m,
                            ch:o.to
                        }
                    }
                }
            }
            return{
                from:a,
                to:c
            }
        };

        var kc=Math.floor(Math.random()*16777215).toString(16),nc,Ta,oc;
        Ta=0;
        var Pb={
            "(":")>",
            ")":"(<",
            "[":"]>",
            "]":"[<",
            "{":"}>",
            "}":"{<"
        },tc=0;
        uc.prototype={
            findNext:function(){
                return this.find(false)
            },
            findPrevious:function(){
                return this.find(true)
            },
            find:function(a){
                function c(i){
                    i={
                        line:i,
                        ch:0
                    };
            
                    d.pos={
                        from:i,
                        to:i
                    };
            
                    return d.atOccurrence=false
                }
                for(var d=this,g=I(a?this.pos.from:this.pos.to);;){
                    if(this.pos=this.matches(a,g)){
                        this.atOccurrence=true;
                        return this.pos.match||true
                    }
                    if(a){
                        if(!g.line)return c(0);
                        g={
                            line:g.line-1,
                            ch:f(g.line-1).text.length
                        }
                    }else{
                        if(g.line==t.size-1)return c(t.size);
                        g={
                            line:g.line+1,
                            ch:0
                        }
                    }
                }
            },
            from:function(){
                if(this.atOccurrence)return{
                    line:this.pos.from.line,
                    ch:this.pos.from.ch
                }
            },
            to:function(){
                if(this.atOccurrence)return{
                    line:this.pos.to.line,
                    ch:this.pos.to.ch
                }
            },
            replace:function(a){
                var c=this;
                this.atOccurrence&&y(function(){
                    c.pos.to=X(a,c.pos.from,c.pos.to)
                })()
            }
        };

        for(var wb in xb)if(xb.propertyIsEnumerable(wb)&&!P.propertyIsEnumerable(wb))P[wb]=xb[wb];return P
    }
    function Ka(b,e){
        if(e===true)return e;
        if(b.copyState)return b.copyState(e);
        var h={},f;
        for(f in e){
            var k=e[f];
            if(k instanceof Array)k=k.concat([]);
            h[f]=k
        }
        return h
    }
    function Qb(b,e,h){
        return b.startState?b.startState(e,
            h):true
    }
    function yb(b){
        this.pos=this.start=0;
        this.string=b
    }
    function mb(b,e,h,f){
        this.from=b;
        this.to=e;
        this.style=h;
        this.set=f
    }
    function yc(b){
        this.to=this.from=b;
        this.line=null
    }
    function wa(b,e){
        this.styles=e||[b,null];
        this.text=b;
        this.height=1;
        this.stateAfter=this.parent=this.hidden=this.marked=this.gutterMarker=this.className=null
    }
    function zb(b,e,h,f){
        for(var k=0,p=0,q=0;p<e;k+=2){
            var B=h[k],z=p+B.length;
            if(q==0){
                z>b&&f.push(B.slice(b-p,Math.min(B.length,e-p)),h[k+1]);
                if(z>=b)q=1
            }else if(q==1)z>e?
                f.push(B.slice(0,e-p),h[k+1]):f.push(B,h[k+1]);
            p=z
        }
    }
    function ub(b){
        this.lines=b;
        this.parent=null;
        for(var e=0,h=b.length,f=0;e<h;++e){
            b[e].parent=this;
            f+=b[e].height
        }
        this.height=f
    }
    function tb(b){
        this.children=b;
        for(var e=0,h=0,f=0,k=b.length;f<k;++f){
            var p=b[f];
            e+=p.chunkSize();
            h+=p.height;
            p.parent=this
        }
        this.size=e;
        this.height=h;
        this.parent=null
    }
    function nb(b){
        if(b.parent==null)return null;
        var e=b.parent;
        b=Za(e.lines,b);
        for(var h=e.parent;h;e=h,h=h.parent)for(var f=0;;++f){
            if(h.children[f]==e)break;
            b+=h.children[f].chunkSize()
        }
        return b
    }
    function eb(b,e){
        var h=0;
            a:do{
                for(var f=0,k=b.children.length;f<k;++f){
                    var p=b.children[f],q=p.height;
                    if(e<q){
                        b=p;
                        continue a
                    }
                    e-=q;
                    h+=p.chunkSize()
                }
                return h
            }while(!b.lines);
        f=0;
        for(k=b.lines.length;f<k;++f){
            p=b.lines[f].height;
            if(e<p)break;
            e-=p
        }
        return h+f
    }
    function gb(b,e){
        var h=0;
            a:do{
                for(var f=0,k=b.children.length;f<k;++f){
                    var p=b.children[f],q=p.chunkSize();
                    if(e<q){
                        b=p;
                        continue a
                    }
                    e-=q;
                    h+=p.height
                }
                return h
            }while(!b.lines);
        for(f=0;f<e;++f)h+=b.lines[f].height;
        return h
    }
    function Vb(){
        this.time=0;
        this.done=[];
        this.undone=[]
    }
    function Oc(){
        pb(this)
    }
    function Xb(b){
        if(!b.stop)b.stop=Oc;
        return b
    }
    function H(b){
        if(b.preventDefault)b.preventDefault();else b.returnValue=false
    }
    function pb(b){
        H(b);
        if(b.stopPropagation)b.stopPropagation();else b.cancelBubble=true
    }
    function Nc(b){
        if(b.which)return b.which;
        else if(b.button&1)return 1;
        else if(b.button&2)return 3;
        else if(b.button&4)return 2
    }
    function E(b,e,h,f){
        function k(p){
            h(p||window.event)
        }
        if(typeof b.addEventListener==
            "function"){
            b.addEventListener(e,k,false);
            if(f)return function(){
                b.removeEventListener(e,k,false)
            }
        }else{
            b.attachEvent("on"+e,k);
            if(f)return function(){
                b.detachEvent("on"+e,k)
            }
        }
    }
    function Tb(){
        this.id=null
    }
    function Yb(b,e){
        if(e==null){
            e=b.search(/[^\s\u00a0]/);
            if(e==-1)e=b.length
        }
        for(var h=0,f=0;h<e;++h)if(b.charAt(h)=="\t")f+=lb-f%lb;else++f;return f
    }
    function Sa(b,e){
        for(var h=b.ownerDocument.body,f=0,k=0,p=false,q=b;q;q=q.offsetParent){
            var B=q.offsetLeft,z=q.offsetTop;
            if(q==h){
                f+=Math.abs(B);
                k+=Math.abs(z)
            }else{
                f+=
                B;
                k+=z
            }
            if(B=e){
                B=q.currentStyle?q.currentStyle:window.getComputedStyle(q,null);
                B=B.position=="fixed"
            }
            if(B)p=true
        }
        h=e&&!p?null:h;
        for(q=b.parentNode;q!=h;q=q.parentNode)if(q.scrollLeft!=null){
            f-=q.scrollLeft;
            k-=q.scrollTop
        }
        return{
            left:f,
            top:k
        }
    }
    function J(b,e){
        return b.line==e.line&&b.ch==e.ch
    }
    function ga(b,e){
        return b.line<e.line||b.line==e.line&&b.ch<e.ch
    }
    function $a(b){
        if(Pc){
            ab.innerHTML="";
            ab.appendChild(document.createTextNode(b))
        }else ab.textContent=b;
        return ab.innerHTML
    }
    function zc(b,e){
        if(!e)return b?
            b.length:0;
        if(!b)return e.length;
        for(var h=b.length,f=e.length;h>=0&&f>=0;--h,--f)if(b.charAt(h)!=e.charAt(f))break;return f+1
    }
    function Za(b,e){
        if(b.indexOf)return b.indexOf(e);
        for(var h=0,f=b.length;h<f;++h)if(b[h]==e)return h;return-1
    }
    F.defaults={
        value:"",
        mode:null,
        theme:"default",
        indentUnit:2,
        indentWithTabs:false,
        tabMode:"classic",
        enterMode:"indent",
        electricChars:true,
        onKeyEvent:null,
        lineWrapping:false,
        lineNumbers:false,
        gutter:false,
        fixedGutter:false,
        firstLineNumber:1,
        readOnly:false,
        smartHome:true,
        onChange:null,
        onCursorActivity:null,
        onGutterClick:null,
        onHighlightComplete:null,
        onFocus:null,
        onBlur:null,
        onScroll:null,
        matchBrackets:false,
        workTime:100,
        workDelay:200,
        undoDepth:40,
        tabindex:null,
        pollForIME:false,
        document:window.document
    };
    
    var Ab={},Ma={};

    F.defineMode=function(b,e){
        if(!F.defaults.mode&&b!="null")F.defaults.mode=b;
        Ab[b]=e
    };
    
    F.defineMIME=function(b,e){
        Ma[b]=e
    };
    
    F.getMode=function(b,e){
        if(typeof e=="string"&&Ma.hasOwnProperty(e))e=Ma[e];
        if(typeof e=="string")var h=e,f={};
        else if(e!=null){
            h=
            e.name;
            f=e
        }
        var k=Ab[h];
        if(!k){
            window.console&&console.warn("No mode "+h+" found, falling back to plain text.");
            return F.getMode(b,"text/plain")
        }
        return k(b,f||{})
    };
    
    F.listModes=function(){
        var b=[],e;
        for(e in Ab)Ab.propertyIsEnumerable(e)&&b.push(e);return b
    };
    
    F.listMIMEs=function(){
        var b=[],e;
        for(e in Ma)Ma.propertyIsEnumerable(e)&&b.push({
            mime:e,
            mode:Ma[e]
        });return b
    };
    
    var xb={};

    F.defineExtension=function(b,e){
        xb[b]=e
    };
    
    F.fromTextArea=function(b,e){
        function h(){
            b.value=q.getValue()
        }
        e||(e={});
        e.value=
        b.value;
        if(!e.tabindex&&b.tabindex)e.tabindex=b.tabindex;
        if(b.form){
            var f=E(b.form,"submit",h,true);
            if(typeof b.form.submit=="function"){
                var k=b.form.submit,p=function(){
                    h();
                    b.form.submit=k;
                    b.form.submit();
                    b.form.submit=p
                };
                
                b.form.submit=p
            }
        }
        b.style.display="none";
        var q=F(function(B){
            b.parentNode.insertBefore(B,b.nextSibling)
        },e);
        q.save=h;
        q.toTextArea=function(){
            h();
            b.parentNode.removeChild(q.getWrapperElement());
            b.style.display="";
            if(b.form){
                f();
                if(typeof b.form.submit=="function")b.form.submit=k
            }
        };
        return q
    };

    F.copyState=Ka;
    F.startState=Qb;
    yb.prototype={
        eol:function(){
            return this.pos>=this.string.length
        },
        sol:function(){
            return this.pos==0
        },
        peek:function(){
            return this.string.charAt(this.pos)
        },
        next:function(){
            if(this.pos<this.string.length)return this.string.charAt(this.pos++)
        },
        eat:function(b){
            var e=this.string.charAt(this.pos);
            if(typeof b=="string"?e==b:e&&(b.test?b.test(e):b(e))){
                ++this.pos;
                return e
            }
        },
        eatWhile:function(b){
            for(var e=this.pos;this.eat(b););
            return this.pos>e
        },
        eatSpace:function(){
            for(var b=
                this.pos;/[\s\u00a0]/.test(this.string.charAt(this.pos));)++this.pos;
            return this.pos>b
        },
        skipToEnd:function(){
            this.pos=this.string.length
        },
        skipTo:function(b){
            b=this.string.indexOf(b,this.pos);
            if(b>-1){
                this.pos=b;
                return true
            }
        },
        backUp:function(b){
            this.pos-=b
        },
        column:function(){
            return Yb(this.string,this.start)
        },
        indentation:function(){
            return Yb(this.string)
        },
        match:function(b,e,h){
            if(typeof b=="string"){
                if((h?this.string.toLowerCase():this.string).indexOf(h?b.toLowerCase():b,this.pos)==this.pos){
                    if(e!==
                        false)this.pos+=b.length;
                    return true
                }
            }else{
                if((b=this.string.slice(this.pos).match(b))&&e!==false)this.pos+=b[0].length;
                return b
            }
        },
        current:function(){
            return this.string.slice(this.start,this.pos)
        }
    };

    F.StringStream=yb;
    mb.prototype={
        attach:function(b){
            this.set.push(b)
        },
        detach:function(b){
            b=Za(this.set,b);
            b>-1&&this.set.splice(b,1)
        },
        split:function(b,e){
            if(this.to<=b&&this.to!=null)return null;
            return new mb(this.from<b||this.from==null?null:this.from-b+e,this.to==null?null:this.to-b+e,this.style,this.set)
        },
        dup:function(){
            return new mb(null,null,this.style,this.set)
        },
        clipTo:function(b,e,h,f,k){
            if(this.from!=null&&this.from>=e)this.from=Math.max(f,this.from)+k;
            if(this.to!=null&&this.to>e)this.to=f<this.to?this.to+k:e;
            if(b&&f>this.from&&(f<this.to||this.to==null))this.from=null;
            if(h&&(e<this.to||this.to==null)&&(e>this.from||this.from==null))this.to=null
        },
        isDead:function(){
            return this.from!=null&&this.to!=null&&this.from>=this.to
        },
        sameSet:function(b){
            return this.set==b.set
        }
    };

    yc.prototype={
        attach:function(b){
            this.line=
            b
        },
        detach:function(b){
            if(this.line==b)this.line=null
        },
        split:function(b,e){
            if(b<this.from){
                this.from=this.to=this.from-b+e;
                return this
            }
        },
        isDead:function(){
            return this.from>this.to
        },
        clipTo:function(b,e,h,f,k){
            if((b||e<this.from)&&(h||f>this.to)){
                this.from=0;
                this.to=-1
            }else if(this.from>e)this.from=this.to=Math.max(f,this.from)+k
        },
        sameSet:function(){
            return false
        },
        find:function(){
            if(!this.line||!this.line.parent)return null;
            return{
                line:nb(this.line),
                ch:this.from
            }
        },
        clear:function(){
            if(this.line){
                var b=
                Za(this.line.marked,this);
                b!=-1&&this.line.marked.splice(b,1);
                this.line=null
            }
        }
    };

    wa.inheritMarks=function(b,e){
        var h=new wa(b),f=e.marked;
        if(f)for(var k=0;k<f.length;++k)if(f[k].to==null&&f[k].style){
            var p=h.marked||(h.marked=[]),q=f[k].dup();
            p.push(q);
            q.attach(h)
        }
        return h
    };
    
    wa.prototype={
        replace:function(b,e,h){
            if(!b&&(e==null||e==this.text.length))this.className=this.gutterMarker=null;
            var f=[],k=this.marked,p=e==null?this.text.length:e;
            zb(0,b,this.styles,f);
            h&&f.push(h,null);
            zb(p,this.text.length,
                this.styles,f);
            this.styles=f;
            this.text=this.text.slice(0,b)+h+this.text.slice(p);
            this.stateAfter=null;
            if(k){
                h=h.length-(p-b);
                f=0;
                for(var q=k[f];f<k.length;++f){
                    q.clipTo(b==null,b||0,e==null,p,h);
                    if(q.isDead()){
                        q.detach(this);
                        k.splice(f--,1)
                    }
                }
            }
        },
        split:function(b,e){
            var h=[e,null],f=this.marked;
            zb(b,this.text.length,this.styles,h);
            h=new wa(e+this.text.slice(b),h);
            if(f)for(var k=0;k<f.length;++k){
                var p=f[k].split(b,e.length);
                if(p){
                    if(!h.marked)h.marked=[];
                    h.marked.push(p);
                    p.attach(h)
                }
            }
            return h
        },
        append:function(b){
            var e=
            this.text.length,h=b.marked,f=this.marked;
            this.text+=b.text;
            zb(0,b.text.length,b.styles,this.styles);
            if(f)for(b=0;b<f.length;++b)if(f[b].to==null)f[b].to=e;if(h&&h.length){
                if(!f)this.marked=f=[];
                b=0;
                    a:for(;b<h.length;++b){
                        var k=h[b];
                        if(!k.from)for(var p=0;p<f.length;++p){
                            var q=f[p];
                            if(q.to==e&&q.sameSet(k)){
                                q.to=k.to==null?null:k.to+e;
                                if(q.isDead()){
                                    q.detach(this);
                                    h.splice(b--,1)
                                }
                                continue a
                            }
                        }
                        f.push(k);
                        k.attach(this);
                        k.from+=e;
                        if(k.to!=null)k.to+=e
                    }
            }
        },
        fixMarkEnds:function(b){
            var e=this.marked;
            b=b.marked;
            if(e)for(var h=0;h<e.length;++h){
                var f=e[h],k=f.to==null;
                if(k&&b)for(var p=0;p<b.length;++p)if(b[p].sameSet(f)){
                    k=false;
                    break
                }
                if(k)f.to=this.text.length
            }
        },
        addMark:function(b){
            b.attach(this);
            if(this.marked==null)this.marked=[];
            this.marked.push(b);
            this.marked.sort(function(e,h){
                return(e.from||0)-(h.from||0)
            })
        },
        highlight:function(b,e){
            var h=new yb(this.text),f=this.styles,k=0,p=false,q=f[0],B;
            for(this.text==""&&b.blankLine&&b.blankLine(e);!h.eol();){
                var z=b.token(h,e),aa=this.text.slice(h.start,h.pos);
                h.start=h.pos;
                if(k&&f[k-1]==z)f[k-2]+=aa;
                else if(aa){
                    if(!p&&(f[k+1]!=z||k&&f[k-2]!=B))p=true;
                    f[k++]=aa;
                    f[k++]=z;
                    B=q;
                    q=f[k]
                }
                if(h.pos>5E3){
                    f[k++]=this.text.slice(h.pos);
                    f[k++]=null;
                    break
                }
            }
            if(f.length!=k){
                f.length=k;
                p=true
            }
            if(k&&f[k-2]!=B)p=true;
            return p||(f.length<5&&this.text.length<10?null:false)
        },
        getTokenAt:function(b,e,h){
            for(var f=new yb(this.text);f.pos<h&&!f.eol();){
                f.start=f.pos;
                var k=b.token(f,e)
            }
            return{
                start:f.start,
                end:f.pos,
                string:f.current(),
                className:k||null,
                state:e
            }
        },
        indentation:function(){
            return Yb(this.text)
        },
        getHTML:function(b,e,h,f){
            function k(na,Da){
                if(na){
                    if(q&&ob&&na.charAt(0)==" ")na="\u00a0"+na.slice(1);
                    q=false;
                    Da?p.push('<span class="',Da,'">',$a(na),"</span>"):p.push($a(na))
                }
            }
            var p=[],q=true;
            if(h)p.push(this.className?'<pre class="'+this.className+'">':"<pre>");
            var B=this.styles,z=this.text,aa=this.marked;
            if(b==e)b=null;
            var da=z.length;
            if(f!=null)da=Math.min(f,da);
            if(!z&&f==null)k(" ",b!=null&&e==null?"CodeMirror-selected":null);
            else if(!aa&&b==null)for(b=f=0;b<da;f+=2){
                e=B[f];
                var la=B[f+1];
                z=
                e.length;
                if(b+z>da)e=e.slice(0,da-b);
                b+=z;
                k(e,la&&"cm-"+la)
            }else{
                f=z=0;
                var ka="",X=-1,Q=null,Oa=function(){
                    if(aa){
                        X+=1;
                        Q=X<aa.length?aa[X]:null
                    }
                };
    
                for(Oa();z<da;){
                    var R=da,ma="";
                    if(b!=null)if(b>z)R=b;
                        else if(e==null||e>z){
                            ma=" CodeMirror-selected";
                            if(e!=null)R=Math.min(R,e)
                        }
                    for(;Q&&Q.to!=null&&Q.to<=z;)Oa();
                    if(Q)if(Q.from>z)R=Math.min(R,Q.from);
                        else{
                            ma+=" "+Q.style;
                            if(Q.to!=null)R=Math.min(R,Q.to)
                        }
                    for(;;){
                        var pa=z+ka.length,ha=la;
                        if(ma)ha=la?la+ma:ma;
                        k(pa>R?ka.slice(0,R-z):ka,ha);
                        if(pa>=R){
                            ka=ka.slice(R-
                                z);
                            z=R;
                            break
                        }
                        z=pa;
                        ka=B[f++];
                        la="cm-"+B[f++]
                    }
                }
                b!=null&&e==null&&k(" ","CodeMirror-selected")
            }
            h&&p.push("</pre>");
            return p.join("")
        },
        cleanUp:function(){
            this.parent=null;
            if(this.marked)for(var b=0,e=this.marked.length;b<e;++b)this.marked[b].detach(this)
        }
    };

    ub.prototype={
        chunkSize:function(){
            return this.lines.length
        },
        remove:function(b,e){
            for(var h=b,f=b+e;h<f;++h){
                var k=this.lines[h];
                k.cleanUp();
                this.height-=k.height
            }
            this.lines.splice(b,e)
        },
        collapse:function(b){
            b.splice.apply(b,[b.length,0].concat(this.lines))
        },
        insertHeight:function(b,e,h){
            this.height+=h;
            this.lines.splice.apply(this.lines,[b,0].concat(e));
            b=0;
            for(h=e.length;b<h;++b)e[b].parent=this
        },
        iterN:function(b,e,h){
            for(e=b+e;b<e;++b)if(h(this.lines[b]))return true
        }
    };

    tb.prototype={
        chunkSize:function(){
            return this.size
        },
        remove:function(b,e){
            this.size-=e;
            for(var h=0;h<this.children.length;++h){
                var f=this.children[h],k=f.chunkSize();
                if(b<k){
                    var p=Math.min(e,k-b),q=f.height;
                    f.remove(b,p);
                    this.height-=q-f.height;
                    if(k==p){
                        this.children.splice(h--,1);
                        f.parent=
                        null
                    }
                    if((e-=p)==0)break;
                    b=0
                }else b-=k
            }
            if(this.size-e<25){
                h=[];
                this.collapse(h);
                this.children=[new ub(h)]
            }
        },
        collapse:function(b){
            for(var e=0,h=this.children.length;e<h;++e)this.children[e].collapse(b)
        },
        insert:function(b,e){
            for(var h=0,f=0,k=e.length;f<k;++f)h+=e[f].height;
            this.insertHeight(b,e,h)
        },
        insertHeight:function(b,e,h){
            this.size+=e.length;
            this.height+=h;
            for(var f=0,k=this.children.length;f<k;++f){
                var p=this.children[f],q=p.chunkSize();
                if(b<=q){
                    p.insertHeight(b,e,h);
                    if(p.lines&&p.lines.length>
                        50){
                        for(;p.lines.length>50;){
                            b=p.lines.splice(p.lines.length-25,25);
                            b=new ub(b);
                            p.height-=b.height;
                            this.children.splice(f+1,0,b);
                            b.parent=this
                        }
                        this.maybeSpill()
                    }
                    break
                }
                b-=q
            }
        },
        maybeSpill:function(){
            if(!(this.children.length<=10)){
                var b=this;
                do{
                    var e=b.children.splice(b.children.length-5,5);
                    e=new tb(e);
                    if(b.parent){
                        b.size-=e.size;
                        b.height-=e.height;
                        var h=Za(b.parent.children,b);
                        b.parent.children.splice(h+1,0,e)
                    }else{
                        h=new tb(b.children);
                        h.parent=b;
                        b.children=[h,e];
                        b=h
                    }
                    e.parent=b.parent
                }while(b.children.length>
                    10);
                b.parent.maybeSpill()
            }
        },
        iter:function(b,e,h){
            this.iterN(b,e-b,h)
        },
        iterN:function(b,e,h){
            for(var f=0,k=this.children.length;f<k;++f){
                var p=this.children[f],q=p.chunkSize();
                if(b<q){
                    q=Math.min(e,q-b);
                    if(p.iterN(b,q,h))return true;
                    if((e-=q)==0)break;
                    b=0
                }else b-=q
            }
        }
    };

    Vb.prototype={
        addChange:function(b,e,h){
            this.undone.length=0;
            var f=+new Date,k=this.done[this.done.length-1];
            if(f-this.time>400||!k||k.start>b+e||k.start+k.added<b-k.added+k.old.length)this.done.push({
                start:b,
                added:e,
                old:h
            });
            else{
                var p=
                0;
                if(b<k.start){
                    for(var q=k.start-b-1;q>=0;--q)k.old.unshift(h[q]);
                    k.added+=k.start-b;
                    k.start=b
                }else if(k.start<b){
                    p=b-k.start;
                    e+=p
                }
                q=k.added-p;
                for(b=h.length;q<b;++q)k.old.push(h[q]);
                if(k.added<e)k.added=e
            }
            this.time=f
        }
    };

    Tb.prototype={
        set:function(b,e){
            clearTimeout(this.id);
            this.id=setTimeout(e,b)
        }
    };

    var wc;
    wc=/MSIE [1-8]\b/.test(navigator.userAgent)?false:"draggable"in document.createElement("div");
    var Ja=/gecko\/\d{7}/i.test(navigator.userAgent),ob=/MSIE \d/.test(navigator.userAgent),sb=/WebKit\//.test(navigator.userAgent),
    cb="\n";
    (function(){
        var b=document.createElement("textarea");
        b.value="foo\nbar";
        if(b.value.indexOf("\r")>-1)cb="\r\n"
    })();
    for(var lb=8,Ya=/Mac/.test(navigator.platform),vc=/Win/.test(navigator.platform),Fa={},Bb=35;Bb<=40;++Bb)Fa[Bb]=Fa["c"+Bb]=true;
    if(document.documentElement.getBoundingClientRect!=null)Sa=function(b,e){
        try{
            var h=b.getBoundingClientRect();
            h={
                top:h.top,
                left:h.left
            }
        }catch(f){
            h={
                top:0,
                left:0
            }
        }
        if(!e)if(window.pageYOffset==null){
            var k=document.documentElement||document.body.parentNode;
            if(k.scrollTop==null)k=document.body;
            h.top+=k.scrollTop;
            h.left+=k.scrollLeft
        }else{
            h.top+=window.pageYOffset;
            h.left+=window.pageXOffset
        }
        return h
    };

    var ab=document.createElement("pre"),Pc=$a("\t")!="\t";
    F.htmlEscape=$a;
    var va,bb,Pa;
    va="\n\nb".split(/\n/).length!=3?function(b){
        for(var e=0,h,f=[];(h=b.indexOf("\n",e))>-1;){
            f.push(b.slice(e,b.charAt(h-1)=="\r"?h-1:h));
            e=h+1
        }
        f.push(b.slice(e));
        return f
    }:function(b){
        return b.split(/\r?\n/)
    };
    
    F.splitLines=va;
    if(window.getSelection){
        bb=function(b){
            try{
                return{
                    start:b.selectionStart,
                    end:b.selectionEnd
                }
            }catch(e){
                return null
            }
        };

        Pa=sb?function(b,e,h){
            if(e==h)b.setSelectionRange(e,h);
            else{
                b.setSelectionRange(e,h-1);
                window.getSelection().modify("extend","forward","character")
            }
        }:function(b,e,h){
            try{
                b.setSelectionRange(e,h)
            }catch(f){}
        }
    }else{
        bb=function(b){
            try{
                var e=b.ownerDocument.selection.createRange()
            }catch(h){
                return null
            }
            if(!e||e.parentElement()!=b)return null;
            var f=b.value,k=f.length,p=b.createTextRange();
            p.moveToBookmark(e.getBookmark());
            var q=b.createTextRange();
            q.collapse(false);
            if(p.compareEndPoints("StartToEnd",q)>-1)return{
                start:k,
                end:k
            };
        
            b=-p.moveStart("character",-k);
            for(e=f.indexOf("\r");e>-1&&e<b;e=f.indexOf("\r",e+1),b++);
            if(p.compareEndPoints("EndToEnd",q)>-1)return{
                start:b,
                end:k
            };
        
            k=-p.moveEnd("character",-k);
            for(e=f.indexOf("\r");e>-1&&e<k;e=f.indexOf("\r",e+1),k++);
            return{
                start:b,
                end:k
            }
        };
    
        Pa=function(b,e,h){
            var f=b.createTextRange();
            f.collapse(true);
            var k=f.duplicate(),p=0;
            b=b.value;
            for(var q=b.indexOf("\n");q>-1&&q<e;q=b.indexOf("\n",q+1))++p;
            for(f.move("character",
                e-p);q>-1&&q<h;q=b.indexOf("\n",q+1))++p;
            k.move("character",h-p);
            f.setEndPoint("EndToEnd",k);
            f.select()
        }
    }
    F.defineMode("null",function(){
        return{
            token:function(b){
                b.skipToEnd()
            }
        }
    });
    F.defineMIME("text/plain","null");
    return F
}();
