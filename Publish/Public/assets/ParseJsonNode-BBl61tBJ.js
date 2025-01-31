import{C as n,o as g,c,a as t,b as s,u as o,X as b,w as l,d,_ as i}from"./vue-DMxFRkQw.js";const p={class:"bg-gray-900/95 backdrop-blur border-[1px] border-green-500/30 rounded-xl p-4 min-w-[300px] relative text-gray-100"},x={class:"flex justify-between items-center mb-4 pb-2 border-b border-green-500/30"},y={class:"flex items-center gap-2"},f={class:"space-y-4"},m={class:"space-y-2"},v={class:"space-y-2"},w={class:"space-y-2"},V={class:"absolute top-1/2 -left-3 w-3 h-6 bg-gray-800 rounded-l-lg border-l-2 border-y-2 border-green-500/50"},h={class:"absolute top-1/2 -right-3 w-3 h-6 bg-gray-800 rounded-r-lg border-r-2 border-y-2 border-green-500/50"},N=Object.assign({nodeMetadata:{category:"Data",icon:n,label:"JSON Extract",initialData:{sourceVariable:"modelEvent",jsonPath:"name",outputKey:"extractedValue"}}},{__name:"ParseJsonNode",props:["data"],emits:["delete"],setup(r){return(u,e)=>(g(),c("div",p,[t("div",x,[t("div",y,[s(o(n),{class:"w-5 h-5 text-green-400"}),e[4]||(e[4]=t("h3",{class:"font-bold text-green-400"},"JSON Extract",-1))]),t("button",{onClick:e[0]||(e[0]=a=>u.$emit("delete")),class:"hover:bg-red-500/20 p-1 rounded transition-colors"},[s(o(b),{class:"w-4 h-4 text-red-400"})])]),t("div",f,[t("div",m,[e[5]||(e[5]=t("label",{class:"text-xs uppercase tracking-wider text-green-400"},"Source Variable",-1)),l(t("input",{"onUpdate:modelValue":e[1]||(e[1]=a=>r.data.sourceVariable=a),placeholder:"Enter source variable name",class:"w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-green-500 text-gray-100"},null,512),[[d,r.data.sourceVariable]])]),t("div",v,[e[6]||(e[6]=t("label",{class:"text-xs uppercase tracking-wider text-green-400"},"JSON Path",-1)),l(t("input",{"onUpdate:modelValue":e[2]||(e[2]=a=>r.data.jsonPath=a),placeholder:"e.g. name.first",class:"w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-green-500 text-gray-100"},null,512),[[d,r.data.jsonPath]])]),t("div",w,[e[7]||(e[7]=t("label",{class:"text-xs uppercase tracking-wider text-green-400"},"Output Key",-1)),l(t("input",{"onUpdate:modelValue":e[3]||(e[3]=a=>r.data.outputKey=a),placeholder:"Variable output key",class:"w-full bg-gray-800 border-0 rounded-lg focus:ring-2 focus:ring-green-500 text-gray-100"},null,512),[[d,r.data.outputKey]])])]),t("div",V,[s(o(i),{type:"target",position:"left"})]),t("div",h,[s(o(i),{type:"source",position:"right",class:"!bg-green-500"})])]))}});export{N as default};
