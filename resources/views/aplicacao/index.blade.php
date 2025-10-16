@extends('app')

@section('content')


<div class="text-center mb-12">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-red-500 rounded-full mb-4">
        <i class="fab fa-youtube text-white text-2xl"></i>
    </div>
    <h1 class="text-4xl font-bold text-gray-800 mb-2">Sumarizador de Vídeos</h1>
    <p class="text-gray-600 text-lg">Transforme qualquer vídeo do YouTube em um resumo inteligente</p>
</div>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-xl p-8">
        @if (session('status'))
            <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700">
                <i class="fas fa-check-circle mr-2"></i>
                {{ session('status') }}
            </div>
        @endif

        <form
            id="summarizerForm"
            class="space-y-6"
            method="POST"
            action="{{ route('resumos.resumir') }}"
        >
            @csrf
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-envelope text-blue-500 mr-2"></i>
                    Seu Email
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    placeholder="exemplo@email.com"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 text-gray-700"
                >
                <p class="text-sm text-gray-500 mt-1">O resumo será enviado para este email</p>
            </div>

            <div>
                <label for="videoUrl" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fab fa-youtube text-red-500 mr-2"></i>
                    URL do Vídeo YouTube
                </label>
                <input
                    type="url"
                    id="videoUrl"
                    name="video_url"
                    required
                    placeholder="https://www.youtube.com/watch?v=..."
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 text-gray-700"
                >
                <p class="text-sm text-gray-500 mt-1">Cole aqui o link completo do vídeo do YouTube</p>
            </div>

            <div class="pt-4">
                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold py-4 px-6 rounded-lg hover:from-blue-600 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform transition duration-200 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                >
                            <span id="submitText">
                                <i class="fas fa-magic mr-2"></i>
                                Gerar Resumo
                            </span>
                    <span id="loadingText" class="hidden">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Processando...
                            </span>
                </button>
            </div>
        </form>
    </div>

    <div class="mt-12 grid md:grid-cols-3 gap-6">
        <div class="text-center p-6 bg-white rounded-xl shadow-md">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-bolt text-blue-500 text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2">Rápido</h3>
            <p class="text-gray-600 text-sm">Resumos gerados em poucos minutos</p>
        </div>

        <div class="text-center p-6 bg-white rounded-xl shadow-md">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-brain text-green-500 text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2">Inteligente</h3>
            <p class="text-gray-600 text-sm">IA avançada para resumos precisos</p>
        </div>

        <div class="text-center p-6 bg-white rounded-xl shadow-md">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-envelope text-purple-500 text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2">Por Email</h3>
            <p class="text-gray-600 text-sm">Receba diretamente na sua caixa de entrada</p>
        </div>
    </div>
</div>

<script>
    const form = document.getElementById('summarizerForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const loadingText = document.getElementById('loadingText');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const videoUrl = document.getElementById('videoUrl').value;

        // Validação básica da URL do YouTube
        const youtubeRegex = /^(https?\:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+/;
        if (!youtubeRegex.test(videoUrl)) {
            alert('Por favor, insira uma URL válida do YouTube.');
            return;
        }

        // Validação do email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Por favor, insira um email válido.');
            return;
        }

        submitBtn.disabled = true;
        submitText.classList.add('hidden');
        loadingText.classList.remove('hidden');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    email: email,
                    video_url: videoUrl,
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (response.status === 422 && data.errors) {
                    const messages = Object.values(data.errors).flat();
                    throw new Error(messages.join('\n'));
                }

                throw new Error(data.message || 'Não foi possível iniciar o processamento. Tente novamente em instantes.');
            }

            alert(`✅ ${data.message || 'Solicitação enviada com sucesso! Verifique seu email em alguns minutos.'}`);
            form.reset();
        } catch (error) {
            console.error('Erro ao enviar solicitação de resumo:', error);
            alert(`❌ ${error.message || 'Ocorreu um erro inesperado. Tente novamente.'}`);
        } finally {
            submitBtn.disabled = false;
            submitText.classList.remove('hidden');
            loadingText.classList.add('hidden');
        }
    });

    // Validação em tempo real da URL do YouTube
    document.getElementById('videoUrl').addEventListener('input', function(e) {
        const url = e.target.value;
        const youtubeRegex = /^(https?\:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+/;

        if (url && !youtubeRegex.test(url)) {
            e.target.classList.add('border-red-500', 'focus:ring-red-500');
            e.target.classList.remove('border-gray-300', 'focus:ring-blue-500');
        } else {
            e.target.classList.remove('border-red-500', 'focus:ring-red-500');
            e.target.classList.add('border-gray-300', 'focus:ring-blue-500');
        }
    });

    // Validação em tempo real do email
    document.getElementById('email').addEventListener('input', function(e) {
        const email = e.target.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (email && !emailRegex.test(email)) {
            e.target.classList.add('border-red-500', 'focus:ring-red-500');
            e.target.classList.remove('border-gray-300', 'focus:ring-blue-500');
        } else {
            e.target.classList.remove('border-red-500', 'focus:ring-red-500');
            e.target.classList.add('border-gray-300', 'focus:ring-blue-500');
        }
    });
</script>
@endsection
