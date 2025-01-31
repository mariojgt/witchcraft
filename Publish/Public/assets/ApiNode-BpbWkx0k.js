import{D as d,o as i,c as u,a as t,b as l,u as o,X as x,w as r,v as c,F as m,r as f,t as y,d as n,e as g,_ as b}from"./vue-BA_kCxex.js";const v={class:"bg-gray-900/95 backdrop-blur border-[1px] border-cyan-500/30 rounded-xl p-4 min-w-[300px] relative text-gray-100"},w={class:"flex justify-between items-center mb-4 pb-2 border-b border-cyan-500/30"},R={class:"flex items-center gap-2"},q={class:"space-y-4"},k={class:"space-y-2"},T={class:"space-y-2"},h=["value"],U={class:"space-y-2"},V={class:"text-xs uppercase tracking-wider text-cyan-400"},E=["placeholder"],D={class:"space-y-2"},N={class:"space-y-2"},P={class:"space-y-2"},S={class:"space-y-2"},A={class:"flex items-center gap-2 text-cyan-400 cursor-pointer"},M={class:"flex items-center gap-2 text-cyan-400 cursor-pointer"},O={class:"absolute top-1/2 -left-3 w-3 h-6 bg-gray-800 rounded-l-lg border-l-2 border-y-2 border-cyan-500/50"},B={class:"absolute top-1/2 -right-3 w-3 h-6 bg-gray-800 rounded-r-lg border-r-2 border-y-2 border-cyan-500/50"},z=Object.assign({nodeMetadata:{category:"Data",icon:d,label:"API Request",initialData:{requestType:"external",method:"GET",url:"",params:"{}",body:"{}",headers:"{}",saveResponse:!0,authenticatedRequest:!1}}},{__name:"ApiNode",props:["data"],emits:["delete"],setup(s){return(p,e)=>(i(),u("div",v,[t("div",w,[t("div",R,[l(o(d),{class:"w-5 h-5 text-cyan-400"}),e[9]||(e[9]=t("h3",{class:"font-bold text-cyan-400"},"API Request",-1))]),t("button",{onClick:e[0]||(e[0]=a=>p.$emit("delete")),class:"hover:bg-red-500/20 p-1 rounded transition-colors"},[l(o(x),{class:"w-4 h-4 text-red-400"})])]),t("div",q,[t("div",k,[e[11]||(e[11]=t("label",{class:"text-xs uppercase tracking-wider text-cyan-400"},"Request Type",-1)),r(t("select",{"onUpdate:modelValue":e[1]||(e[1]=a=>s.data.requestType=a),class:"w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-cyan-500 text-gray-100"},e[10]||(e[10]=[t("option",{value:"external"},"External API",-1),t("option",{value:"internal"},"Internal Route",-1)]),512),[[c,s.data.requestType]])]),t("div",T,[e[12]||(e[12]=t("label",{class:"text-xs uppercase tracking-wider text-cyan-400"},"Method",-1)),r(t("select",{"onUpdate:modelValue":e[2]||(e[2]=a=>s.data.method=a),class:"w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-cyan-500 text-gray-100"},[(i(),u(m,null,f(["GET","POST","PUT","PATCH","DELETE"],a=>t("option",{key:a,value:a,class:"bg-gray-800"},y(a),9,h)),64))],512),[[c,s.data.method]])]),t("div",U,[t("label",V,y(s.data.requestType==="external"?"Endpoint URL":"Route Name"),1),r(t("input",{"onUpdate:modelValue":e[3]||(e[3]=a=>s.data.url=a),placeholder:s.data.requestType==="external"?"https://api.example.com/endpoint":"users.show",class:"w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-cyan-500 text-gray-100"},null,8,E),[[n,s.data.url]])]),t("div",D,[e[13]||(e[13]=t("label",{class:"text-xs uppercase tracking-wider text-cyan-400"},"Request Parameters",-1)),r(t("textarea",{"onUpdate:modelValue":e[4]||(e[4]=a=>s.data.params=a),placeholder:"JSON parameters (query params or route parameters)",rows:"2",class:"w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-cyan-500 text-gray-100 resize-none"},null,512),[[n,s.data.params]])]),t("div",N,[e[14]||(e[14]=t("label",{class:"text-xs uppercase tracking-wider text-cyan-400"},"Request Body",-1)),r(t("textarea",{"onUpdate:modelValue":e[5]||(e[5]=a=>s.data.body=a),placeholder:"JSON payload",rows:"3",class:"w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-cyan-500 text-gray-100 resize-none"},null,512),[[n,s.data.body]])]),t("div",P,[e[15]||(e[15]=t("label",{class:"text-xs uppercase tracking-wider text-cyan-400"},"Headers",-1)),r(t("textarea",{"onUpdate:modelValue":e[6]||(e[6]=a=>s.data.headers=a),placeholder:"JSON headers",rows:"2",class:"w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-cyan-500 text-gray-100 resize-none"},null,512),[[n,s.data.headers]])]),t("div",S,[t("label",A,[r(t("input",{type:"checkbox","onUpdate:modelValue":e[7]||(e[7]=a=>s.data.saveResponse=a),class:"rounded border-0 bg-gray-700 text-cyan-500 focus:ring-cyan-500"},null,512),[[g,s.data.saveResponse]]),e[16]||(e[16]=t("span",{class:"text-sm"},"Save Response",-1))]),t("label",M,[r(t("input",{type:"checkbox","onUpdate:modelValue":e[8]||(e[8]=a=>s.data.authenticatedRequest=a),class:"rounded border-0 bg-gray-700 text-cyan-500 focus:ring-cyan-500"},null,512),[[g,s.data.authenticatedRequest]]),e[17]||(e[17]=t("span",{class:"text-sm"},"Authenticated Request",-1))])])]),t("div",O,[l(o(b),{type:"target",position:"left"})]),t("div",B,[l(o(b),{type:"source",position:"right",class:"!bg-cyan-500"})])]))}});export{z as default};
