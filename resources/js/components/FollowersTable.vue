<template>
    <div class="w-full rounded border border-gray-800/60 bg-[#111622]/40 overflow-hidden font-sans text-slate-200">
        
        <div class="border-b border-gray-800/60 bg-[#111a30] p-4 flex items-center justify-between">
            <div class="flex items-center space-x-2 text-purple-400">
                <span class="text-[10px] font-black tracking-widest uppercase">{{ title }}</span>
            </div>
            <span class="text-[9px] font-mono font-bold text-slate-500 uppercase select-none">[ FOLLOWERS ]</span>
        </div>

        <div v-if="!followers || followers.length === 0" class="p-8 text-center text-xs uppercase font-mono text-slate-500 tracking-wider">
            No followers yet.
        </div>

        <div v-else class="w-full">
            
            <div class="block md:hidden divide-y divide-gray-800/40">
                <div 
                    v-for="follower in followers" 
                    :key="follower.code" 
                    class="p-4 bg-[#111622]/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-[#111622]/40 transition-colors"
                >
                    <div class="flex items-center space-x-3">
                        <img 
                            :src="follower.avatar" 
                            :alt="follower.name" 
                            class="w-10 h-10 rounded border border-[#232d42] bg-[#070b14]"
                        >
                        <div class="space-y-0.5">
                            <h4 class="text-xs font-black tracking-wide text-white uppercase font-mono">
                                {{ follower.name }}
                            </h4>
                            <div class="flex items-center space-x-2 text-[9px] text-slate-400 font-semibold uppercase font-mono">
                                <span>CODE: <span class="text-sky-400 font-bold">{{ follower.code }}</span></span>
                                <span>•</span>
                                <span>{{ follower.followed_at }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <Link 
                            :href="`/profile/${follower.code}`"
                            class="w-full flex items-center justify-center bg-[#1c273a] hover:bg-purple-500 hover:text-[#070b14] border border-[#2b3a54] hover:border-purple-500 text-[10px] font-black tracking-widest text-slate-300 py-2 rounded uppercase transition-all duration-200"
                        >
                            VIEW PROFILE
                        </Link>
                    </div>
                </div>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-800/60 bg-[#0f1422] text-[10px] font-black tracking-widest text-slate-400 uppercase font-mono">
                            <th class="py-3 px-4">NAME</th>
                            <th class="py-3 px-4">CODE</th>
                            <th class="py-3 px-4">FOLLOWER SINCE</th>
                            <th class="py-3 px-4 text-right">ACTION</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/30">
                        <tr 
                            v-for="follower in followers" 
                            :key="follower.code"
                            class="text-xs border-b border-gray-800/20 bg-[#111622]/10 hover:bg-[#111622]/40 transition-colors group"
                        >
                            <td class="py-3.5 px-4 flex items-center space-x-3">
                                <img 
                                    :src="follower.avatar" 
                                    :alt="follower.name" 
                                    class="w-8 h-8 rounded border border-[#232d42] bg-[#070b14]"
                                >
                                <span class="font-bold tracking-wide text-white uppercase font-sans">
                                    {{ follower.name }}
                                </span>
                            </td>
                            
                            <td class="py-3.5 px-4 font-mono font-black tracking-wider text-sky-400">
                                {{ follower.code }}
                            </td>
                            
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-400 uppercase">
                                {{ follower.followed_at }}
                            </td>
                            
                            <td class="py-3.5 px-4 text-right">
                                <Link 
                                    :href="`/profile/${follower.code}`"
                                    class="inline-block text-[10px] font-black tracking-widest text-purple-400 hover:text-[#070b14] border border-purple-500/20 hover:border-purple-500 bg-purple-500/5 hover:bg-purple-500 px-3 py-1.5 rounded uppercase transition-all duration-200"
                                >
                                    VIEW PROFILE →
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

// Establish strictly checked layout array definitions
defineProps({
    followers: {
        type: Array,
        required: true,
        default: () => []
    },
    title: {
        type: String,
        required: true,
        default: () => 'Followers'
    }
});
</script>