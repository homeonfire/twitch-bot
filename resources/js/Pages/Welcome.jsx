import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Terminal, Mic, Brain, Sparkles, Twitch, ArrowRight, Zap, Shield, MessageSquare } from 'lucide-react';

export default function Welcome({ auth }) {
    const containerVariants = {
        hidden: { opacity: 0 },
        visible: {
            opacity: 1,
            transition: { staggerChildren: 0.2 }
        }
    };

    const itemVariants = {
        hidden: { opacity: 0, y: 20 },
        visible: { opacity: 1, y: 0, transition: { duration: 0.6 } }
    };

    return (
        <div className="min-h-screen bg-[#0a0a0b] text-gray-100 font-sans selection:bg-purple-500/30 overflow-x-hidden">
            <Head>
                <title>AI Stream Bot | Умный бот для твоего Twitch</title>
                <meta name="description" content="Добавь на стрим виртуального соведущего. Бот общается как живой зритель, отвечает голосом в OBS и запоминает все шутки и мемы твоего канала." />
                <meta name="keywords" content="twitch бот, ии для стрима, нейросеть для твича, ai stream bot, умный чат бот, озвучка чата" />
            </Head>

            <div className="fixed top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[600px] bg-purple-600/10 blur-[150px] rounded-full pointer-events-none z-0" />
            
            <nav className="fixed w-full z-50 bg-[#0a0a0b]/80 backdrop-blur-md border-b border-white/5">
                <div className="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
                    <div className="flex items-center gap-2 font-bold text-2xl tracking-tight relative z-10">
                        <Sparkles className="text-purple-500 w-6 h-6" />
                        <span><span className="text-purple-500">AI</span>StreamBot</span>
                    </div>
                    <div className="flex items-center gap-6 relative z-10">
                        <a href="https://twitch.tv/trenertvs" target="_blank" rel="noreferrer" className="flex items-center gap-2 text-sm font-bold text-white hover:text-purple-300 transition drop-shadow-[0_0_8px_rgba(145,70,255,0.8)]">
                            <Twitch className="w-5 h-5 text-purple-400" />
                            LIVE ТЕСТ
                        </a>
                    </div>
                </div>
            </nav>

            <main className="relative z-10">
                <section className="pt-40 pb-20 lg:pt-48 lg:pb-32 max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <motion.div 
                        variants={containerVariants} 
                        initial="hidden" 
                        animate="visible"
                        className="text-left"
                    >
                        <motion.div variants={itemVariants} className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-purple-500/10 border border-purple-500/20 text-sm font-medium text-purple-300 mb-6">
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-purple-500"></span>
                            </span>
                            Закрытый бета-тест
                        </motion.div>

                        <motion.h1 variants={itemVariants} className="text-5xl lg:text-7xl font-extrabold tracking-tight mb-6 leading-[1.1]">
                            Бот, который <br />
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-blue-500">
                                шарит за твой лор
                            </span>
                        </motion.h1>

                        <motion.p variants={itemVariants} className="text-xl text-gray-400 mb-10 leading-relaxed max-w-xl">
                            Добавь на стрим виртуального бро. Он общается как живой зритель, отвечает крутым голосом прямо на трансляции и запоминает каждую шутку, мем и факт о твоем канале.
                        </motion.p>

                        <motion.div variants={itemVariants} className="flex flex-col sm:flex-row gap-4">
                            <a href="#cta-closed" className="group relative px-8 py-4 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl transition-all shadow-[0_0_30px_rgba(145,70,255,0.3)] hover:shadow-[0_0_50px_rgba(145,70,255,0.5)] flex items-center justify-center gap-2">
                                Подать заявку на тест
                                <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                            </a>
                            <a href="#how-it-works" className="px-8 py-4 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold rounded-xl transition-all flex items-center justify-center">
                                Как это работает?
                            </a>
                        </motion.div>
                    </motion.div>

                    <motion.div 
                        initial={{ opacity: 0, x: 50, rotateY: -10 }}
                        animate={{ opacity: 1, x: 0, rotateY: 0 }}
                        transition={{ duration: 0.8, delay: 0.2 }}
                        className="relative perspective-1000"
                    >
                        <div className="absolute -inset-1 bg-gradient-to-r from-purple-600 to-blue-600 rounded-2xl blur opacity-20 animate-pulse"></div>
                        <div className="relative bg-[#18181b] border border-white/10 rounded-2xl p-6 shadow-2xl flex flex-col gap-4">
                            <div className="flex items-center justify-between border-b border-white/10 pb-4 mb-2">
                                <div className="flex items-center gap-2">
                                    <Twitch className="w-5 h-5 text-purple-400" />
                                    <span className="font-semibold text-sm">Пример из чата</span>
                                </div>
                                <span className="text-xs text-gray-500 flex items-center gap-1">
                                    <span className="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span> LIVE
                                </span>
                            </div>
                            
                            <ChatMsg user="CS_Tryhard" color="text-green-400" msg="Бот, напомни, что стример советовал брать на эко?" />
                            
                            <div className="bg-white/5 rounded-lg p-3 text-xs text-gray-400 font-mono flex flex-col gap-1 my-2">
                                <div className="flex items-center gap-2"><Zap className="w-3 h-3 text-blue-400" /> Копаюсь в памяти...</div>
                                <div className="text-gray-500 pl-5">Вспомнил: "На эко-раунде всегда беру P250 и флешку" (было 3 дня назад)</div>
                            </div>

                            <ChatMsg user="AI_Bot" isBot color="text-purple-400" msg="Слушай, CS_Tryhard, на прошлом стриме он говорил, что лучшая страта на эко — это закуп P250 и флешки. Дешево и сердито! 😎" />
                            
                            <ChatMsg user="CS_Tryhard" color="text-green-400" msg="Ахах, реально, спасибо! Бот гений." />
                        </div>
                    </motion.div>
                </section>

                <section id="features" className="py-24 bg-[#0e0e12] border-y border-white/5">
                    <div className="max-w-7xl mx-auto px-6">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl md:text-5xl font-bold mb-4">Обычные боты — <span className="text-purple-500">прошлый век</span></h2>
                            <p className="text-gray-400 text-lg max-w-2xl mx-auto">Команды через восклицательный знак уже никого не удивляют. Наш бот понимает сленг, рофлит вместе с чатом и делает шоу.</p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <InteractiveFeatureCard 
                                icon={<Brain className="w-8 h-8 text-purple-400" />}
                                title="Память как у слона"
                                description="Бот помнит, о чем вы говорили, и отлично знает локальные мемы с прошлых стримов. Никаких золотых рыбок."
                            />
                            <InteractiveFeatureCard 
                                icon={<Mic className="w-8 h-8 text-blue-400" />}
                                title="Живая озвучка"
                                description="Бот отвечает не только текстом, но и голосом прямо на стриме. Можно выбрать супер-реалистичный голос диктора или смешной."
                            />
                            <InteractiveFeatureCard 
                                icon={<Shield className="w-8 h-8 text-emerald-400" />}
                                title="Уникальный характер"
                                description="Ты сам решаешь, кем будет бот: токсичным киберкотлетой, милой аниме-тян или мудрым дедом. Он подыграет любой атмосфере."
                            />
                        </div>
                    </div>
                </section>

                <section id="how-it-works" className="py-24 relative">
                    <div className="max-w-7xl mx-auto px-6 text-center">
                        <h2 className="text-3xl md:text-5xl font-bold mb-16">Запуск за <span className="text-purple-500">3 минуты</span></h2>
                        
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-12 relative">
                            <div className="hidden md:block absolute top-8 left-[16%] right-[16%] h-[2px] bg-gradient-to-r from-purple-500/0 via-purple-500/50 to-purple-500/0"></div>
                            
                            <Step number="1" title="Заявка" desc="Напиши мне, и я выдам тебе доступ к закрытому тесту платформы." />
                            <Step number="2" title="Настройка" desc="Подключаем бота к твоему Twitch-каналу и настраиваем его характер." />
                            <Step number="3" title="Готово!" desc="Добавляем красивый виджет в OBS, и бот начинает развлекать твоих зрителей." />
                        </div>
                    </div>
                </section>
                
                <section id="cta-closed" className="py-20 relative overflow-hidden">
                    <div className="absolute inset-0 bg-purple-900/20"></div>
                    <div className="max-w-4xl mx-auto px-6 relative z-10 text-center bg-[#0a0a0b]/50 backdrop-blur-xl border border-purple-500/30 p-12 rounded-3xl">
                        <MessageSquare className="w-12 h-12 text-purple-400 mx-auto mb-6" />
                        <h2 className="text-4xl font-bold mb-6">Хочешь такого же бота на свой канал?</h2>
                        <p className="text-xl text-gray-300 mb-8">Места в закрытом бета-тесте ограничены. Успей подать заявку и получи фишку, которой еще нет у других стримеров.</p>
                        <a href="https://t.me/igreskiv" target="_blank" rel="noreferrer" className="inline-block px-10 py-5 bg-white text-black font-extrabold rounded-xl hover:bg-gray-200 transition-transform hover:scale-105 shadow-[0_0_40px_rgba(255,255,255,0.5)]">
                            Записаться на закрытый тест
                        </a>
                    </div>
                </section>
            </main>

            <footer className="border-t border-white/5 bg-[#050505] pt-16 pb-8 text-sm text-gray-400">
                <div className="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                    <div>
                        <div className="flex items-center gap-2 font-bold text-xl text-white mb-4">
                            <Sparkles className="text-purple-500 w-5 h-5" />
                            AI Stream Bot
                        </div>
                        <p className="leading-relaxed">Умный ИИ-помощник для создателей контента. Делаем стримы интереснее, а чат — активнее.</p>
                    </div>
                    <div>
                        <h4 className="text-white font-semibold mb-4 text-base">Продукт</h4>
                        <ul className="space-y-2">
                            <li><a href="#features" className="hover:text-purple-400 transition">Что умеет бот</a></li>
                            <li><a href="#how-it-works" className="hover:text-purple-400 transition">Как подключить</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 className="text-white font-semibold mb-4 text-base">Контакты</h4>
                        <ul className="space-y-2">
                            <li><a href="https://t.me/igreskiv" className="hover:text-purple-400 transition">Связь со мной (Telegram)</a></li>
                            <li><a href="https://twitch.tv/trenertvs" className="hover:text-purple-400 transition">Мой Twitch-канал</a></li>
                        </ul>
                    </div>
                </div>
                <div className="max-w-7xl mx-auto px-6 text-center border-t border-white/5 pt-8">
                    <p>© {new Date().getFullYear()} AI Stream Bot. Делаем стриминг веселее.</p>
                </div>
            </footer>
        </div>
    );
}

function ChatMsg({ user, color, msg, isBot = false }) {
    return (
        <div className="flex gap-3 text-sm leading-relaxed items-start">
            {isBot && <div className="w-6 h-6 rounded-full bg-purple-600 flex items-center justify-center text-[10px] mt-1 shrink-0">AI</div>}
            <div>
                <span className={`font-bold ${color} mr-2`}>{user}</span>
                <span className="text-gray-300">{msg}</span>
            </div>
        </div>
    );
}

function InteractiveFeatureCard({ icon, title, description }) {
    return (
        <motion.div 
            whileHover={{ y: -5 }}
            className="group relative bg-[#121215] border border-white/5 p-8 rounded-3xl overflow-hidden transition-all hover:border-purple-500/50"
        >
            <div className="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div className="relative z-10">
                <div className="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 group-hover:bg-purple-500/20 transition-all duration-300">
                    {icon}
                </div>
                <h3 className="text-2xl font-bold text-white mb-4">{title}</h3>
                <p className="text-gray-400 leading-relaxed text-lg">{description}</p>
            </div>
        </motion.div>
    );
}

function Step({ number, title, desc }) {
    return (
        <div className="relative z-10 flex flex-col items-center group">
            <div className="w-16 h-16 rounded-2xl bg-darker border border-purple-500/30 bg-[#0a0a0b] flex items-center justify-center text-2xl font-bold text-purple-400 mb-6 group-hover:border-purple-500 group-hover:scale-110 group-hover:shadow-[0_0_20px_rgba(145,70,255,0.4)] transition-all">
                {number}
            </div>
            <h3 className="text-xl font-bold text-white mb-3">{title}</h3>
            <p className="text-gray-400">{desc}</p>
        </div>
    );
}