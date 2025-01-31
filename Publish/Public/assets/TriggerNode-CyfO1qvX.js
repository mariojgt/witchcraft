import{o as n,c as b,a as t,b as s,u as r,X as g,w as i,d as o,_ as c}from"./vue-CIlr9HO7.js";import{D as d}from"./database-C3pDS3iy.js";const p={class:"bg-gray-900/95 backdrop-blur border-[1px] border-blue-500/30 rounded-xl p-4 min-w-[300px] relative text-gray-100"},x={class:"flex justify-between items-center mb-4 pb-2 border-b border-blue-500/30"},m={class:"flex items-center gap-2"},y={class:"space-y-4"},f={class:"space-y-2"},v={class:"space-y-2"},w={class:"space-y-2"},V={class:"absolute top-1/2 -right-3 w-3 h-6 bg-gray-800 rounded-r-lg border-r-2 border-y-2 border-blue-500/50"},T=Object.assign({nodeMetadata:{category:"Trigger",icon:d,label:"Trigger",initialData:{variableName:"userStatus",initialValue:"active",outputKey:"status"}}},{__name:"TriggerNode",props:["data"],emits:["delete"],setup(a){return(u,e)=>(n(),b("div",p,[t("div",x,[t("div",m,[s(r(d),{class:"w-5 h-5 text-blue-400"}),e[4]||(e[4]=t("h3",{class:"font-bold text-blue-400"},"Trigger",-1))]),t("button",{onClick:e[0]||(e[0]=l=>u.$emit("delete")),class:"hover:bg-red-500/20 p-1 rounded transition-colors"},[s(r(g),{class:"w-4 h-4 text-red-400"})])]),t("div",y,[t("div",f,[e[5]||(e[5]=t("label",{class:"text-xs uppercase tracking-wider text-blue-400"},"Variable Name",-1)),i(t("input",{"onUpdate:modelValue":e[1]||(e[1]=l=>a.data.variableName=l),placeholder:"Enter variable name",class:"w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-100"},null,512),[[o,a.data.variableName]])]),t("div",v,[e[6]||(e[6]=t("label",{class:"text-xs uppercase tracking-wider text-blue-400"},"Initial Value",-1)),i(t("input",{"onUpdate:modelValue":e[2]||(e[2]=l=>a.data.initialValue=l),placeholder:"Enter initial value",class:"w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-100"},null,512),[[o,a.data.initialValue]])]),t("div",w,[e[7]||(e[7]=t("label",{class:"text-xs uppercase tracking-wider text-blue-400"},"Output Key",-1)),i(t("input",{"onUpdate:modelValue":e[3]||(e[3]=l=>a.data.outputKey=l),placeholder:"Variable output key",class:"w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-100"},null,512),[[o,a.data.outputKey]])])]),t("div",V,[s(r(c),{type:"source",position:"right",class:"!bg-blue-500"})])]))}});export{T as default};
