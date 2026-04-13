import{j as t}from"./vendor-mui-2cHuhLvv.js";import{l as j,r as x,L as i}from"./vendor-react-D-Ndkw3L.js";import{u as _,G as g,b as w,L as m,c as N,s as v}from"./index-C2O1kTSp.js";import{u as y}from"./blogsStore-DwZCJdX7.js";import{u as D}from"./vendor-i18n-Dzw_n1Df.js";import"./vendor-zustand-BLdIKjHb.js";import"./vendor-axios-C7aFLusC.js";import"./vendor-toast-CM0JZaQ8.js";import"./vendor-swiper-adhIqUnC.js";function E(){const{t:o}=D(),{lang:r}=_(),{slug:a}=j(),{singleBlog:e,loadingSingle:p,errorSingle:d,fetchBlogBySlug:h}=y();x.useEffect(()=>{a&&h(a,r)},[a,r]);const b=s=>new Date(s).toLocaleDateString(r==="ar"?"ar-SA":"en-US",{year:"numeric",month:"long",day:"numeric"}),n=s=>r==="ar"?s.title_ar:s.title_en||s.title,u=s=>r==="ar"?s.content_ar:s.content_en||s.content,c=s=>r==="ar"?s.short_description_ar:s.short_description_en||s.short_description,f=x.useMemo(()=>{if(e)return[g.article(e,r),g.breadcrumb([{name:r==="ar"?"الرئيسية":"Home",url:`/${r}/`},{name:r==="ar"?"المدونة":"Blog",url:`/${r}/blog`},{name:n(e),url:`/${r}/blog/${e.slug}`}])]},[e,r]);return w({title:e?n(e):r==="ar"?"المدونة":"Blog",description:e&&(r==="ar"?e.meta_description_ar||e.short_description_ar:e.meta_description_en||e.short_description_en||e.short_description)||"",keywords:e&&(r==="ar"?e.meta_keywords_ar:e.meta_keywords_en||e.meta_keywords)||void 0,lang:r,ogType:"article",ogImage:e?.featured_image,jsonLd:f}),p?t.jsx(m,{children:t.jsx(N,{})}):d||!e?t.jsx(m,{children:t.jsxs("div",{className:"container mx-auto px-4 py-32 text-center mt-32",children:[t.jsx("p",{className:"text-red-500 text-base sm:text-lg",children:d||o("BlogPostNotFound")}),t.jsx(i,{to:`/${r}/blog`,className:"mt-4 inline-block text-[#211C4D] hover:underline text-sm sm:text-base",children:o("BackToBlog")})]})}):t.jsx(m,{children:t.jsxs("div",{className:"container mx-auto px-2 sm:px-4 md:px-6 lg:px-[90px] py-4 sm:py-6 md:py-8",dir:r==="ar"?"rtl":"ltr",children:[t.jsx("div",{className:`py-2 sm:py-4 mb-4 sm:mb-6 ${r==="ar"?"text-right":"text-left"}`,children:t.jsx("nav",{"aria-label":"breadcrumb",dir:r==="ar"?"rtl":"ltr",children:t.jsxs("ol",{className:"flex flex-wrap items-center gap-x-3 sm:gap-x-6 gap-y-2 sm:gap-y-3 text-sm sm:text-base md:text-lg leading-none",children:[t.jsx("li",{className:"flex-shrink-0",children:t.jsx(i,{to:`/${r}/blog`,className:"text-[#181D25] hover:text-[#211C4D] font-[500] underline underline-offset-4 sm:underline-offset-6 decoration-1 whitespace-nowrap",children:o("Blog")})}),t.jsx("li",{className:"flex-shrink-0",children:t.jsx("span",{className:"text-[#333D4C] font-[500] whitespace-nowrap text-xs sm:text-sm md:text-base line-clamp-1",children:n(e)})})]})})}),t.jsx("div",{className:"w-full h-[250px] sm:h-[350px] md:h-[450px] lg:h-[550px] rounded-xl sm:rounded-2xl overflow-hidden mb-6 sm:mb-8 shadow-[0px_4px_16px_0px_rgba(0,0,0,0.1)]",children:t.jsx("img",{src:e.featured_image,alt:n(e),className:"w-full h-full object-cover"})}),t.jsxs("div",{className:"max-w-4xl mx-auto px-2 sm:px-0",children:[t.jsx("div",{className:"text-xs sm:text-sm md:text-base text-[#F3AC5D] font-medium mb-3 sm:mb-4",children:b(e.published_at)}),t.jsx("h1",{className:"text-[#211C4D] font-bold text-2xl sm:text-3xl md:text-4xl lg:text-5xl mb-4 sm:mb-6 leading-tight",children:n(e)}),c(e)&&t.jsx("div",{className:"bg-gray-50 rounded-lg p-3 sm:p-4 md:p-6 mb-6 sm:mb-8",children:t.jsx("p",{className:"text-gray-700 text-base sm:text-lg md:text-xl leading-relaxed",children:c(e)})}),t.jsx("div",{className:"prose prose-sm sm:prose-base md:prose-lg max-w-none mb-6 sm:mb-8 blog-content",dangerouslySetInnerHTML:{__html:v(u(e))},style:{color:"#333",lineHeight:"1.8"}}),t.jsx("style",{children:`
            .blog-content h1,
            .blog-content h2,
            .blog-content h3,
            .blog-content h4 {
              color: #211C4D;
              font-weight: bold;
              margin-top: 1.5rem;
              margin-bottom: 0.75rem;
            }
            @media (min-width: 640px) {
              .blog-content h1,
              .blog-content h2,
              .blog-content h3,
              .blog-content h4 {
                margin-top: 2rem;
                margin-bottom: 1rem;
              }
            }
            .blog-content p {
              margin-bottom: 1rem;
              font-size: 0.95rem;
            }
            @media (min-width: 640px) {
              .blog-content p {
                margin-bottom: 1.5rem;
                font-size: 1.1rem;
              }
            }
            .blog-content img {
              border-radius: 8px;
              margin: 1.5rem 0;
              max-width: 100%;
              height: auto;
            }
            @media (min-width: 640px) {
              .blog-content img {
                border-radius: 12px;
                margin: 2rem 0;
              }
            }
            .blog-content a {
              color: #F3AC5D;
              text-decoration: underline;
            }
            .blog-content ul,
            .blog-content ol {
              margin: 1rem 0;
              padding-${r==="ar"?"right":"left"}: 1.5rem;
            }
            @media (min-width: 640px) {
              .blog-content ul,
              .blog-content ol {
                margin: 1.5rem 0;
                padding-${r==="ar"?"right":"left"}: 2rem;
              }
            }
            .blog-content li {
              margin-bottom: 0.5rem;
            }
            @media (min-width: 640px) {
              .blog-content li {
                margin-bottom: 0.75rem;
              }
            }
          `}),e.images&&e.images.length>0&&t.jsx("div",{className:"grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-6 sm:mb-8",children:e.images.map((s,l)=>t.jsx("div",{className:"rounded-lg overflow-hidden",children:t.jsx("img",{src:s,alt:`${n(e)} - ${l+1}`,className:"w-full h-auto object-cover"})},l))}),t.jsx("div",{className:"mt-8 sm:mt-12 pt-6 sm:pt-8 border-t border-gray-200",children:t.jsxs(i,{to:`/${r}/blog`,className:"inline-flex items-center gap-2 sm:gap-3 text-[#211C4D] hover:text-[#F3AC5D] font-semibold text-base sm:text-lg transition-all duration-300 group",children:[t.jsx("span",{className:`transform transition-transform duration-300 ${r==="ar"?"group-hover:-translate-x-1":"group-hover:translate-x-1"}`,children:r==="ar"?"←":"→"}),t.jsx("span",{children:o("BackToBlog")})]})})]})]})})}export{E as default};
