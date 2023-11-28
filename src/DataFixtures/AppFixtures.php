<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Placeholder;
use App\Entity\PromptTemplate;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private array $users = [
        ['admin@gmail.com', 'password', ['ROLE_USER', 'ROLE_ADMIN'], 'Admin',],
    ];
    private array $categories = [
        ['examples'],
        ['tones'],
        ['intros'],
    ];
    /** key, headline, categories, value */
    private array $placeholders = [
        [
            'blog_post',
            'Travel Blog Entry Example',
            ['examples'],
            'Discovering the hidden gems of Italy: My journey through Tuscany was filled with unexpected delights, from quaint hilltop villages to stunning vineyards. The beauty of the Italian countryside is truly breathtaking.',
        ],
        [
            'research_summary',
            'Scientific Research Summary',
            ['intros'],
            'Exploring the depths of quantum computing: This study delves into the advancements in quantum algorithms, highlighting the potential for revolutionizing data processing and encryption methods.',
        ],
    ];

    private array $promptTemplates = [
        [
            'Blog Post Generator',
            "Please write a blog post about your recent trip to the Scottish Highlands. Include experiences with local culture and nature. Reference below for style.\n\nREFERENCE:\n\n{blog_post}",
        ],
        [
            'Research Article Opener',
            "Draft an introduction for an article on the latest developments in renewable energy technologies. Use the following research summary for inspiration.\n\nREFERENCE:\n\n{research_summary}",
        ],
    ];

    public function __construct(private readonly UserPasswordHasherInterface $passwordEncoder)
    {
    }


    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $data = $this->users[0];
        $user->setEmail($data[0]);
        $user->setPassword(
            $this->passwordEncoder->hashPassword(
                $user,
                $data[1],
            )
        );
        $user->setRoles($data[2]);
        if (isset($data[3])) {
            $user->setName($data[3]);
        }
        $this->addReference('user', $user);
        $manager->persist($user);
        $manager->flush();

        foreach ($this->categories as $data) {
            $category = new Category();
            $category->setTitle($data[0]);
            $category->setUser($this->getReference('user'));
            $this->addReference('category_'.$category->getTitle(), $category);
            $manager->persist($category);
        }
        $manager->flush();

        foreach ($this->placeholders as $data) {
            $placeholder = new Placeholder();
            $placeholder->setUser($this->getReference('user'));
            $placeholder->setKey($data[0]);
            $placeholder->setHeadline($data[1]);
            foreach ($data[2] as $cData) {
                $placeholder->addCategory($this->getReference('category_'.$cData));
            }
            $placeholder->setValue($data[3]);
            $manager->persist($placeholder);
        }
        $manager->flush();

        foreach ($this->promptTemplates as $data) {
            $promptTemplate = new PromptTemplate();
            $promptTemplate->setUser($this->getReference('user'));
            $promptTemplate->setTitle($data[0]);
            $promptTemplate->setContent($data[1]);
            $manager->persist($promptTemplate);
        }
        $manager->flush();
    }
}
