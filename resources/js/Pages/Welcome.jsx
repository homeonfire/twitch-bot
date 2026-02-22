import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Terminal, Mic, Brain, Sparkles, Twitch, ArrowRight } from 'lucide-react';

export default function Welcome({ auth }) {
    return (
        <div className="min-h-screen bg-[#0a0a0b] text-gray-100 font-sans selection:bg-purple-500/30 overflow-x-hidden">
            <Head title="AI Stream Bot | Нейросеть для твоего Twitch" />

            {/* Декоративные фоновые свечения */}
            <div className="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-purple-600/20 blur-[150px] rounded-full pointer-events-none" />
            
            {/* Навигация (Glassmorphism) */}
            <nav className="fixed w-full z-50 bg-[#0a0a0b]/80 backdrop-blur-md border-b border-white/5">
                <div className="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
                    <div className="flex items-center gap-2 font-bold text-2xl tracking-tight">
                        <Sparkles className="text-purple-500 w-6 h-6" />
                        <span><span className="text-purple-500">AI</span>StreamBot</span>
                    </div>
                    <div className="flex items-center gap-6">
                        <a href="https://twitch.tv/trenertvs" target="_blank" rel="noreferrer" className="flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-purple-400 transition">
                            <Twitch className="w-5 h-5" />
                            Тест на стриме
                        </a>
                        {auth.user ? (
                            <Link href={route('dashboard')} className="text-sm font-medium text-white bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition">
                                Кабинет
                            </Link>
                        ) : (
                            <Link href={route('login')} className="text-sm font-medium text-white bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition">
                                Войти
                            </Link>
                        )}
                    </div>
                </div>
            </nav>

            {/* Главный экран */}
            <main className="relative pt-40 pb-24">
                <div className="max-w-7xl mx-auto px-6 text-center">
                    <motion.div 
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.6 }}
                        className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-purple-500/10 border border-purple-500/20 text-sm font-medium text-purple-300 mb-8"
                    >
                        <span className="relative flex h-2 w-2">
                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                            <span className="relative inline-flex rounded-full h-2 w-2 bg-purple-500"></span>
                        </span>
                        Версия 1.0 уже в сети
                    </motion.div>

                    <motion.h1 
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.6, delay: 0.1 }}
                        className="text-5xl md:text-7xl font-extrabold tracking-tight mb-8 leading-tight"
                    >
                        Нейросеть, которая <br />
                        <span className="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-blue-500">
                            знает твой лор
                        </span>
                    </motion.h1>

                    <motion.p 
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.6, delay: 0.2 }}
                        className="max-w-2xl mx-auto text-xl text-gray-400 mb-12 leading-relaxed"
                    >
                        Забудь про глупых ботов-командников. Наш ИИ общается как живой зритель, отвечает голосом в OBS и помнит, какую пушку ты брал на эко-раунде неделю назад.
                    </motion.p>

                    <motion.div 
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.6, delay: 0.3 }}
                        className="flex flex-col sm:flex-row justify-center gap-4"
                    >
                        <a 
                            href="https://t.me/ТВОЙ_НИК_В_ТГ" 
                            target="_blank" 
                            rel="noreferrer"
                            className="group relative px-8 py-4 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl transition-all shadow-[0_0_30px_rgba(145,70,255,0.3)] hover:shadow-[0_0_50px_rgba(145,70,255,0.5)] flex items-center justify-center gap-2 overflow-hidden"
                        >
                            <span className="relative z-10 flex items-center gap-2">
                                Хочу такого бота
                                <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                            </span>
                        </a>
                    </motion.div>
                </div>
            </main>

            {/* Карточки с фичами */}
            <section className="py-24 bg-[#0e0e12] border-t border-white/5 relative z-10">
                <div className="max-w-7xl mx-auto px-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        
                        <FeatureCard 
                            icon={<Brain className="w-6 h-6 text-purple-400" />}
                            title="Глубокая память (RAG)"
                            description="Собственная векторная база данных. Бот запоминает каждого зрителя, локальные мемы и детали твоих прошлых стримов."
                            delay={0.2}
                        />
                        <FeatureCard 
                            icon={<Mic className="w-6 h-6 text-blue-400" />}
                            title="Голосовые ответы в OBS"
                            description="Ты спрашиваешь голосом — бот отвечает голосом. Поддержка премиальных дикторов от ElevenLabs и классического Google TTS."
                            delay={0.3}
                        />
                        <FeatureCard 
                            icon={<Terminal className="w-6 h-6 text-emerald-400" />}
                            title="Мощный интеллект"
                            description="Под капотом работает DeepSeek. Задай боту любой характер (токсичный эксперт, милашка, философ), и он будет отыгрывать его 24/7."
                            delay={0.4}
                        />

                    </div>
                </div>
            </section>

            {/* Футер */}
            <footer className="border-t border-white/5 py-12 text-center text-gray-500 text-sm">
                <p>© 2026 AI Stream Bot. Создано с душой для стримеров.</p>
            </footer>
        </div>
    );
}

// Отдельный компонент для карточек (чтобы код был чистым)
function FeatureCard({ icon, title, description, delay }) {
    return (
        <motion.div 
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5, delay }}
            className="bg-white/[0.02] border border-white/5 p-8 rounded-2xl hover:bg-white/[0.04] transition-colors group"
        >
            <div className="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                {icon}
            </div>
            <h3 className="text-xl font-bold text-gray-100 mb-3">{title}</h3>
            <p className="text-gray-400 leading-relaxed">
                {description}
            </p>
        </motion.div>
    );
}