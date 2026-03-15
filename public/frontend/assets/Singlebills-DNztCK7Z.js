import{j as e}from"./vendor-mui-2cHuhLvv.js";import{g as R,l as E,r as w}from"./vendor-react-D-Ndkw3L.js";import{u as I,L as v}from"./index-BtFYVm4w.js";import{S as k}from"./Sidebar-BXAJO-Lz.js";import{u as A}from"./vendor-i18n-Dzw_n1Df.js";import"./addressStore-CnYmK8jy.js";import"./profileStore-B1MQxXhL.js";import{a as O}from"./ordersStore-D6z--aH9.js";import{r as F}from"./vendor-html2pdf-DS46S6o7.js";import"./vendor-zustand-BLdIKjHb.js";import"./vendor-axios-C7aFLusC.js";import"./vendor-swiper-adhIqUnC.js";import"./vendor-toast-CM0JZaQ8.js";function q({title:d,points:r,btn:g,onPrint:c,onShare:x}){const{t:p}=A(),i=()=>{c?c():window.print()};return e.jsx("div",{children:e.jsxs("div",{className:"w-full min-h-[45px] p-4 my-5 bg-[#E5E5E5] flex flex-col sm:flex-row items-center justify-between gap-3 rounded-[8px]",children:[e.jsx("p",{className:"text-[#211C4D] text-[20px] sm:text-[24px] font-[500]",children:d}),e.jsx("p",{className:"text-[#211C4DCC] text-[14px] sm:text-[16px] font-[500]",children:r}),g?e.jsxs("div",{className:"flex items-center gap-2",children:[e.jsx("button",{className:"w-[129px] h-[35px] bg-[#211C4D] rounded-[8px] text-white text-[14px] sm:text-[16px] whitespace-nowrap",onClick:i,children:p("PrintInvoice",{defaultValue:"طباعه الفاتوره"})}),x&&e.jsxs("button",{className:"h-[35px] px-3 bg-[#211C4D] rounded-[8px] text-white text-[14px] sm:text-[16px] whitespace-nowrap flex items-center gap-1.5",onClick:x,title:p("ShareInvoice",{defaultValue:"مشاركة الفاتورة"}),children:[e.jsxs("svg",{xmlns:"http://www.w3.org/2000/svg",width:"18",height:"18",viewBox:"0 0 24 24",fill:"none",stroke:"currentColor",strokeWidth:"2",strokeLinecap:"round",strokeLinejoin:"round",children:[e.jsx("circle",{cx:"18",cy:"5",r:"3"}),e.jsx("circle",{cx:"6",cy:"12",r:"3"}),e.jsx("circle",{cx:"18",cy:"19",r:"3"}),e.jsx("line",{x1:"8.59",y1:"13.51",x2:"15.42",y2:"17.49"}),e.jsx("line",{x1:"15.41",y1:"6.51",x2:"8.59",y2:"10.49"})]}),p("Share",{defaultValue:"مشاركة"})]})]}):null]})})}var U=F();const D=R(U);function et(){const{id:d}=E(),{currentInvoice:r,singleLoading:g,singleError:c,fetchInvoiceById:x}=O(),p=w.useRef(null),{lang:i="ar"}=I();A();const t=i==="ar";w.useEffect(()=>{d&&x(parseInt(d))},[d,x]);const P=()=>{if(!r?.order)return{subtotal:0,tax:0,total:0};const o=r.order.items.reduce((l,m)=>l+m.total,0),n=o*.15,h=o-n,S=r.order.shipping||0,y=o+S;return{subtotal:h,tax:n,total:y}},{subtotal:b,tax:u,total:f}=P(),L=()=>{if(!r)return;const o=window.open("","_blank");if(!o){alert("Please allow popups to print the invoice.");return}const a=`
        <!DOCTYPE html>
        <html lang="${i}" dir="${t?"rtl":"ltr"}">
        <head>
            <meta charset="UTF-8">
            <title>${t?"فاتورة":"Invoice"} #${r.invoice_number}</title>
            <script src="https://cdn.tailwindcss.com"><\/script>
            <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
            <style>
                body { font-family: 'Cairo', 'Arial', sans-serif; }
                @media print {
                    .no-print { display: none; }
                    /* Ensure background colors print */
                    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                }
            </style>
        </head>
        <body class="bg-gray-100 p-0 m-0" dir="${t?"rtl":"ltr"}">
            <div class="max-w-[800px] mx-auto bg-white p-10 my-10 shadow-lg print:shadow-none print:m-0 print:w-full print:max-w-none">
                
                <!-- Header -->
                <div class="flex justify-between items-start mb-10 pb-5 border-b-4 border-[#2c3e50]">
                    <div class="text-[#2c3e50] ${t?"text-right":"text-left"}">
                        <h1 class="text-[28px] font-bold mb-2">City Phones</h1>
                        <p class="text-sm text-[#7f8c8d]">${t?"المملكة العربية السعودية":"Kingdom of Saudi Arabia"}</p>
                    </div>
                    <div class="${t?"text-left":"text-right"}">
                        <h2 class="text-[#211C4D] text-[32px] font-bold mb-2">${t?"فاتورة":"INVOICE"}</h2>
                        <p class="text-sm my-1 text-[#555]"><strong>${t?"رقم الفاتورة:":"Invoice No:"}</strong> <span class="text-[#2c3e50]">${r.invoice_number}</span></p>
                        <p class="text-sm my-1 text-[#555]"><strong>${t?"تاريخ الفاتورة:":"Date:"}</strong> <span class="text-[#2c3e50]">${r.invoice_date}</span></p>
                        <p class="text-sm my-1 text-[#555]"><strong>${t?"رقم الطلب:":"Order No:"}</strong> <span class="text-[#2c3e50]">${r.order_number||"-"}</span></p>
                    </div>
                </div>

                <!-- Details -->
                <div class="flex justify-between mb-10 gap-5 flex-wrap">
                    <div class="flex-1 min-w-[200px]">
                        <h3 class="text-[#2c3e50] text-base font-bold mb-3 uppercase border-b border-gray-100 pb-1">${t?"معلومات الطلب":"Order Info"}</h3>
                        <p class="text-sm text-[#555] my-1"><strong>${t?"الحالة:":"Status:"}</strong> ${t?"مكتمل":"Completed"}</p>
                        <p class="text-sm text-[#555] my-1"><strong>${t?"طريقة الدفع:":"Payment:"}</strong> ${t?r.order.payment_method?.name_ar||"-":r.order.payment_method?.name_en||r.order.payment_method?.name_ar||"-"}</p>
                    </div>
                    <!-- Placeholders for Ship To if needed later -->
                     <div class="flex-1 min-w-[200px]">
                        <h3 class="text-[#2c3e50] text-base font-bold mb-3 uppercase border-b border-gray-100 pb-1">${t?"العميل":"Customer"}</h3>
                        <p class="text-sm text-[#555] my-1"><strong>${t?"الاسم:":"Name:"}</strong> ${r.order.location?.first_name||""} ${r.order.location?.last_name||(t?"عميل":"Customer")}</p>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="w-full border-collapse mb-8">
                    <thead>
                        <tr class="bg-[#211C4D] text-white">
                            <th class="p-3 ${t?"text-right":"text-left"} text-sm font-semibold">${t?"المنتج":"Product"}</th>
                            <th class="p-3 ${t?"text-right":"text-left"} text-sm font-semibold">${t?"الكمية":"Qty"}</th>
                            <th class="p-3 ${t?"text-right":"text-left"} text-sm font-semibold">${t?"السعر":"Price"}</th>
                            <th class="p-3 ${t?"text-right":"text-left"} text-sm font-semibold">${t?"الإجمالي":"Total"}</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${r.order.items.map(n=>`
                            <tr class="hover:bg-gray-50 border-b border-gray-200">
                                <td class="p-3 text-sm text-[#333] ${t?"text-right":"text-left"}">
                                    <strong class="block">${i==="ar"?n.product.name_ar||n.product.name:n.product.name_en||n.product.name}</strong>
                                    ${n.product_option?`<small class="text-gray-500">${n.product_option.value}</small>`:""}
                                </td>
                                <td class="p-3 text-sm text-[#333] ${t?"text-right":"text-left"}">${n.quantity}</td>
                                <td class="p-3 text-sm text-[#333] ${t?"text-right":"text-left"}">${n.price.toLocaleString()} ${t?"رس":"SAR"}</td>
                                <td class="p-3 text-sm text-[#333] font-bold ${t?"text-right":"text-left"}">${n.total.toLocaleString()} ${t?"رس":"SAR"}</td>
                            </tr>
                        `).join("")}
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="flex ${t?"justify-start":"justify-end"} mb-10">
                    <table class="w-[350px]">
                        <tr class="border-b border-gray-200">
                            <td class="p-2 text-sm text-[#7f8c8d] font-medium ${t?"text-right":"text-left"}">${t?"المجموع الفرعي":"Subtotal"}</td>
                            <td class="p-2 text-sm ${t?"text-left":"text-right"} font-semibold text-[#333]">${b.toLocaleString()} ${t?"رس":"SAR"}</td>
                        </tr>
                        <tr class="border-b border-gray-200">
                            <td class="p-2 text-sm text-[#7f8c8d] font-medium ${t?"text-right":"text-left"}">${t?"الضريبة":"Tax"}</td>
                            <td class="p-2 text-sm ${t?"text-left":"text-right"} font-semibold text-[#333]">${u.toLocaleString()} ${t?"رس":"SAR"}</td>
                        </tr>
                        <tr class="border-b border-gray-200">
                            <td class="p-2 text-sm text-[#7f8c8d] font-medium ${t?"text-right":"text-left"}">${t?"الشحن":"Shipping"}</td>
                            <td class="p-2 text-sm ${t?"text-left":"text-right"} font-semibold text-[#333]">${r.order.shipping?.toLocaleString()||0} ${t?"رس":"SAR"}</td>
                        </tr>
                        <tr class="bg-[#2c3e50] text-white text-lg font-bold">
                            <td class="p-3 ${t?"text-right":"text-left"} border-none">${t?"الإجمالي":"Total"}</td>
                            <td class="p-3 ${t?"text-left":"text-right"} border-none">${f.toLocaleString()} ${t?"رس":"SAR"}</td>
                        </tr>
                    </table>
                </div>

                <!-- Footer -->
                <div class="text-center pt-5 border-t-2 border-gray-100 text-xs text-[#7f8c8d] mt-12">
                    <p class="mb-1">${t?"شكراً لتعاملكم معنا!":"Thank you for your business!"}</p>
                    <p>${t?"هذه فاتورة إلكترونية صالحة بدون توقيع.":"This is a valid electronic invoice without signature."}</p>
                </div>
            </div>
            <script>
                window.onload = function() { window.print(); }
            <\/script>
        </body>
        </html>
    `;o.document.write(a),o.document.close()},[T,j]=w.useState(!1),z=async()=>{if(!r||T)return;j(!0);let o=null;try{const a=`${t?"فاتورة":"Invoice"} #${r.invoice_number}`,n=t?`فاتورة من City Phones - رقم ${r.invoice_number}`:`Invoice from City Phones - #${r.invoice_number}`,h=window.location.href;if(typeof navigator<"u"&&(navigator.userAgentData?.mobile===!0||/Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent||""))&&typeof navigator.share=="function")try{await navigator.share({title:a,text:n,url:h});return}catch(s){if(s?.name==="AbortError")return}const y=`
        <!DOCTYPE html>
        <html lang="${i}" dir="${t?"rtl":"ltr"}">
        <head>
          <meta charset="UTF-8">
          <style>
            body { margin: 0; padding: 0; background: white; color: black; font-family: 'Cairo', 'Arial', sans-serif; }
            * { box-sizing: border-box; }
          </style>
        </head>
        <body>
          <div style="direction: ${t?"rtl":"ltr"}; padding: 40px; max-width: 800px; margin: 0 auto;">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 4px solid #2c3e50;">
              <div style="color: #2c3e50; text-align: ${t?"right":"left"}">
                <h1 style="font-size: 28px; font-weight: bold; margin-bottom: 8px; margin-top: 0;">City Phones</h1>
                <p style="font-size: 14px; color: #7f8c8d; margin: 0;">${t?"المملكة العربية السعودية":"Kingdom of Saudi Arabia"}</p>
              </div>
              <div style="text-align: ${t?"left":"right"};">
                <h2 style="color: #211C4D; font-size: 32px; font-weight: bold; margin-bottom: 8px; margin-top: 0;">${t?"فاتورة":"INVOICE"}</h2>
                <p style="font-size: 14px; margin: 4px 0; color: #555;"><strong>${t?"رقم الفاتورة:":"Invoice No:"}</strong> <span style="color: #2c3e50;">${r.invoice_number}</span></p>
                <p style="font-size: 14px; margin: 4px 0; color: #555;"><strong>${t?"تاريخ الفاتورة:":"Date:"}</strong> <span style="color: #2c3e50;">${r.invoice_date}</span></p>
                <p style="font-size: 14px; margin: 4px 0; color: #555;"><strong>${t?"رقم الطلب:":"Order No:"}</strong> <span style="color: #2c3e50;">${r.order_number||"-"}</span></p>
              </div>
            </div>

            <!-- Details -->
            <div style="display: flex; justify-content: space-between; margin-bottom: 40px; gap: 20px; flex-wrap: wrap;">
              <div style="flex: 1; min-width: 200px;">
                <h3 style="color: #2c3e50; font-size: 16px; font-weight: bold; margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 4px; margin-top: 0;">${t?"معلومات الطلب":"Order Info"}</h3>
                <p style="font-size: 14px; color: #555; margin: 4px 0;"><strong>${t?"الحالة:":"Status:"}</strong> ${t?"مكتمل":"Completed"}</p>
                <p style="font-size: 14px; color: #555; margin: 4px 0;"><strong>${t?"طريقة الدفع:":"Payment:"}</strong> ${t?r.order.payment_method?.name_ar||"-":r.order.payment_method?.name_en||r.order.payment_method?.name_ar||"-"}</p>
              </div>
              <div style="flex: 1; min-width: 200px;">
                <h3 style="color: #2c3e50; font-size: 16px; font-weight: bold; margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 4px; margin-top: 0;">${t?"العميل":"Customer"}</h3>
                <p style="font-size: 14px; color: #555; margin: 4px 0;"><strong>${t?"الاسم:":"Name:"}</strong> ${r.order.location?.first_name||""} ${r.order.location?.last_name||(t?"عميل":"Customer")}</p>
              </div>
            </div>

            <!-- Items Table -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 32px;">
              <thead>
                <tr style="background-color: #211C4D; color: white;">
                  <th style="padding: 12px; text-align: ${t?"right":"left"}; font-size: 14px;">${t?"المنتج":"Product"}</th>
                  <th style="padding: 12px; text-align: ${t?"right":"left"}; font-size: 14px;">${t?"الكمية":"Qty"}</th>
                  <th style="padding: 12px; text-align: ${t?"right":"left"}; font-size: 14px;">${t?"السعر":"Price"}</th>
                  <th style="padding: 12px; text-align: ${t?"right":"left"}; font-size: 14px;">${t?"الإجمالي":"Total"}</th>
                </tr>
              </thead>
              <tbody>
                ${r.order.items.map(s=>`
                  <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px; font-size: 14px; color: #333; text-align: ${t?"right":"left"}">
                      <strong>${i==="ar"?s.product.name_ar||s.product.name:s.product.name_en||s.product.name}</strong>
                      ${s.product_option?`<br/><small style="color: #9ca3af;">${s.product_option.value}</small>`:""}
                    </td>
                    <td style="padding: 12px; font-size: 14px; color: #333; text-align: ${t?"right":"left"}">${s.quantity}</td>
                    <td style="padding: 12px; font-size: 14px; color: #333; text-align: ${t?"right":"left"}">${s.price.toLocaleString()} ${t?"رس":"SAR"}</td>
                    <td style="padding: 12px; font-size: 14px; color: #333; font-weight: bold; text-align: ${t?"right":"left"}">${s.total.toLocaleString()} ${t?"رس":"SAR"}</td>
                  </tr>
                `).join("")}
              </tbody>
            </table>

            <!-- Totals -->
            <div style="display: flex; justify-content: ${t?"flex-start":"flex-end"}; margin-bottom: 40px;">
              <table style="width: 350px;">
                <tr style="border-bottom: 1px solid #e5e7eb;">
                  <td style="padding: 8px; font-size: 14px; color: #7f8c8d; text-align: ${t?"right":"left"};">${t?"المجموع الفرعي":"Subtotal"}</td>
                  <td style="padding: 8px; font-size: 14px; text-align: ${t?"left":"right"}; font-weight: 600; color: #333;">${b.toLocaleString()} ${t?"رس":"SAR"}</td>
                </tr>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                  <td style="padding: 8px; font-size: 14px; color: #7f8c8d; text-align: ${t?"right":"left"};">${t?"الضريبة":"Tax"}</td>
                  <td style="padding: 8px; font-size: 14px; text-align: ${t?"left":"right"}; font-weight: 600; color: #333;">${u.toLocaleString()} ${t?"رس":"SAR"}</td>
                </tr>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                  <td style="padding: 8px; font-size: 14px; color: #7f8c8d; text-align: ${t?"right":"left"};">${t?"الشحن":"Shipping"}</td>
                  <td style="padding: 8px; font-size: 14px; text-align: ${t?"left":"right"}; font-weight: 600; color: #333;">${r.order.shipping?.toLocaleString()||0} ${t?"رس":"SAR"}</td>
                </tr>
                <tr style="background-color: #2c3e50; color: white; font-size: 18px; font-weight: bold;">
                  <td style="padding: 12px; text-align: ${t?"right":"left"};">${t?"الإجمالي":"Total"}</td>
                  <td style="padding: 12px; text-align: ${t?"left":"right"};">${f.toLocaleString()} ${t?"رس":"SAR"}</td>
                </tr>
              </table>
            </div>

            <!-- Footer -->
            <div style="text-align: center; padding-top: 20px; border-top: 2px solid #f3f4f6; font-size: 12px; color: #7f8c8d; margin-top: 48px;">
              <p style="margin-bottom: 4px;">${t?"شكراً لتعاملكم معنا!":"Thank you for your business!"}</p>
              <p>${t?"هذه فاتورة إلكترونية صالحة بدون توقيع.":"This is a valid electronic invoice without signature."}</p>
            </div>
          </div>
        </body>
        </html>
      `;if(typeof D!="function")throw new Error("مكتبة التصدير غير متوفرة");o=document.createElement("iframe"),o.style.position="fixed",o.style.left="-10000px",o.style.top="0",o.style.width="1000px",o.style.height="1000px",document.body.appendChild(o);const l=o.contentDocument||o.contentWindow?.document;if(!l)throw new Error("فشل في إنشاء إطار للطباعة");l.open(),l.write(y),l.close(),await new Promise(s=>setTimeout(s,500));const m=`Invoice-${r.invoice_number}.pdf`,N=await Promise.race([D().set({margin:10,filename:m,image:{type:"jpeg",quality:.98},html2canvas:{scale:2,useCORS:!0,onclone:s=>{s.querySelectorAll('link[rel="stylesheet"], style').forEach($=>$.remove())}},jsPDF:{unit:"mm",format:"a4",orientation:"portrait"}}).from(l.body).toPdf().output("blob"),new Promise((s,$)=>setTimeout(()=>$(new Error(t?"انتهت مهلة تجهيز ملف المشاركة":"Share file generation timed out")),2e4))]),C=new File([N],m,{type:"application/pdf"});if(typeof navigator.share=="function"&&typeof navigator.canShare=="function")try{if(navigator.canShare({files:[C]})){await navigator.share({title:a,text:n,files:[C]});return}}catch{}if(typeof navigator.share=="function")try{await navigator.share({title:a,text:n,url:h});return}catch(s){if(s?.name==="AbortError")return}const _=URL.createObjectURL(N);try{const s=document.createElement("a");s.href=_,s.download=m,document.body.appendChild(s),s.click(),document.body.removeChild(s)}finally{URL.revokeObjectURL(_)}}catch(a){a?.name!=="AbortError"&&alert(`حدث خطأ أثناء المشاركة: ${a.message||String(a)}`)}finally{o&&o.parentNode&&o.parentNode.removeChild(o),j(!1)}};return g?e.jsx(v,{children:e.jsx("div",{className:"flex justify-center items-center h-64",children:e.jsx("p",{children:t?"جاري تحميل تفاصيل الفاتورة...":"Loading invoice details..."})})}):c||!r?e.jsx(v,{children:e.jsx("div",{className:"flex justify-center items-center h-64",children:e.jsx("p",{className:"text-red-500",children:c||(t?"لم يتم العثور على الفاتورة":"Invoice not found")})})}):e.jsx("div",{children:e.jsx(v,{children:e.jsxs("div",{className:"flex flex-col md:flex-row justify-center gap-[30px] mt-[80px] mb-20",children:[e.jsx(k,{}),e.jsxs("div",{className:"md:w-[883px] w-full",children:[e.jsx(q,{title:`${t?"تفاصيل الفاتورة":"Invoice Details"} #${r.invoice_number}`,btn:!0,onPrint:L,onShare:z}),e.jsxs("div",{ref:p,children:[e.jsx("div",{className:"overflow-x-auto w-[100vw] md:w-[60vw] lg:w-full xl:w-[883px] md:px-0 px-[20px]",children:e.jsxs("table",{dir:t?"rtl":"ltr",className:`w-full border-separate border-spacing-y-3 mt-6 text-center min-w-[883px] ${t?"!rtl":"!ltr"}`,children:[e.jsx("thead",{}),e.jsx("tbody",{className:"bg-white",children:r.order.items.map(o=>e.jsxs("tr",{className:"h-[108px]",children:[e.jsx("td",{className:"text-[#211C4D] w-[32%] border-b font-[500] py-4",children:e.jsxs("div",{className:`flex justify-start w-[243px] h-[76px] p-1 bg-[#cbcbcb2b] border rounded-[8px] items-center gap-3 ${t?"rtl":"ltr"}`,children:[e.jsx("img",{src:o.product.main_image||"https://via.placeholder.com/75",alt:t?o.product.name_ar||o.product.name:o.product.name_en||o.product.name,className:"w-[75px] h-[76px] object-contain rounded-md"}),e.jsxs("div",{className:`w-[140px] ${t?"text-start":"text-left"}`,children:[e.jsx("p",{className:"font-[600] text-[14px] text-[#211C4D] line-clamp-2 leading-tight",title:o.product.name,children:t?o.product.name_ar||o.product.name:o.product.name_en||o.product.name}),e.jsxs("div",{className:`flex flex-col ${t?"items-end":"items-start"}`,children:[e.jsxs("p",{className:"text-[14px] text-[#6c6c80] mt-1",children:["×",o.quantity]}),o.product_option&&e.jsx("p",{className:"text-[14px] text-[#6c6c80]",children:o.product_option.value})]})]})]})}),e.jsx("td",{className:"border-b py-4",children:e.jsx("div",{className:`flex justify-center w-full items-center gap-3 ${t?"rtl":"ltr"}`,children:e.jsxs("p",{children:[o.price.toLocaleString()," ",t?"رس":"SAR"]})})}),e.jsx("td",{className:"text-[#211C4D] border-b text-center font-[500] py-4",children:e.jsx("div",{className:"w-full flex items-center justify-center",children:e.jsx("p",{children:o.quantity})})}),e.jsx("td",{className:"text-[#211C4D] border-b font-[500] py-4",children:e.jsx("div",{className:"w-full flex items-center justify-center",children:e.jsxs("p",{children:[o.total.toLocaleString()," ",t?"رس":"SAR"]})})})]},o.id))})]})}),e.jsxs("div",{className:"md:w-[883px] shadow-[0_4px_8px_rgba(0,0,0,0.2)] rounded-xl bg-white py-4 px-6 mt-4",children:[e.jsx("h2",{className:"text-[24px] font-[500] text-[#211C4D] mb-4",children:t?"تفاصيل الدفع":"Payment Details"}),e.jsxs("div",{className:"flex items-center justify-between",children:[e.jsx("p",{className:"text-[16px] font-[500] text-[#211C4D]",children:t?"المجموع الفرعي":"Subtotal"}),e.jsxs("p",{className:"font-[300] text-[16px] text-[#211C4D]",children:[b.toLocaleString()," ",t?"رس":"SAR"]})]}),e.jsxs("div",{className:"flex items-center my-5 justify-between",children:[e.jsx("p",{className:"text-[16px] font-[500] text-[#211C4D]",children:t?"الضريبة المقدرة":"Estimated Tax"}),e.jsxs("p",{className:"font-[300] text-[16px] text-[#211C4D]",children:[u.toLocaleString()," ",t?"رس":"SAR"]})]}),e.jsxs("div",{className:"flex items-center justify-between",children:[e.jsx("p",{className:"text-[16px] font-[500] text-[#211C4D]",children:t?"تكلفة الشحن":"Shipping Cost"}),e.jsxs("p",{className:"font-[300] text-[16px] text-[#211C4D]",children:[r.order.shipping?.toLocaleString()||"0"," ",t?"رس":"SAR"]})]}),e.jsxs("div",{className:"flex items-center mt-6 justify-between",children:[e.jsx("p",{className:"text-[24px] font-[500] text-[#211C4D]",children:t?"المجموع الاجمالي":"Total Amount"}),e.jsxs("p",{className:"text-[24px] font-[500] text-[#211C4D]",children:[f.toLocaleString()," ",t?"رس":"SAR"]})]})]}),e.jsxs("div",{className:"md:w-[883px] flex-col md:flex-row flex items-center justify-between shadow-[0_4px_8px_rgba(0,0,0,0.2)] rounded-xl bg-white md:py-4 px-6 py-4 mt-4",children:[e.jsxs("div",{className:`text-center ${t?"md:text-start":"md:text-left"}`,children:[e.jsx("h2",{className:"text-[24px] font-[500] text-[#211C4D] mb-4",children:t?"معلومات الدفع":"Payment Information"}),e.jsx("p",{className:`text-[#211C4D] text-[16px] font-[500] md:mt-2 ${t?"md:mr-2":"md:ml-2"}`,children:t?"طريقه الدفع":"Payment Method"}),e.jsx("p",{className:`text-[24px] text-[#211C4D] font-[500] ${t?"mr-2":"ml-2"}`,children:t?r.order.payment_method?.name_ar||"بطاقة ائتمان":r.order.payment_method?.name_en||r.order.payment_method?.name_ar||"Credit Card"})]}),e.jsxs("div",{className:"mt-4 md:mt-0",children:[e.jsx("p",{className:"text-[16px] font-[500] text-[#211C4D]",children:t?"المبلغ الإجمالي":"Total Amount"}),e.jsx("p",{className:"text-[24px] font-[500] text-[#211C4D]",children:f.toLocaleString()})]}),e.jsx("div",{children:e.jsx("img",{src:"/src/assets/images/sucsespayment.png",className:"w-[100px] h-[122px] object-contain",alt:""})})]})]})]})]})})})}export{et as default};
